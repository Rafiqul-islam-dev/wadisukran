<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderTicket;
use App\Models\Product;
use App\Models\RiskCapGroup;
use App\Models\User;
use App\Services\LockTicketService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LockTicketController extends Controller
{
    public function __construct(private readonly LockTicketService $lockTicketService)
    {
    }

    public function index(Request $request)
    {
        $lockedTickets = $this->lockTicketService->detect($request);
        $heldTickets = $this->lockTicketService->heldTickets($request);

        return Inertia::render('Orders/LockTickets', [
            'lockedTickets' => $lockedTickets,
            'heldTickets' => $heldTickets,
            'products' => Product::withTrashed()->orderBy('title')->get(['id', 'title', 'product_number']),
            'users' => User::where('user_type', 'agent')->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only([
                'date_from',
                'time_from',
                'date_to',
                'time_to',
                'product_id',
                'user_id',
            ]),
        ]);
    }

    public function hold(Request $request)
    {
        $validated = $request->validate([
            'ticket_ids' => ['required', 'array', 'min:1'],
            'ticket_ids.*' => ['integer', 'exists:order_tickets,id'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $tickets = OrderTicket::query()
            ->whereIn('id', $validated['ticket_ids'])
            ->where('is_claimed', 0)
            ->where('is_winner', 0)
            ->whereHas('order', function ($query) {
                $query->where('status', 'Printed')
                    ->where('is_claimed', 0)
                    ->where('is_winner', 0);
            })
            ->get();

        if ($tickets->isEmpty()) {
            return back()->with('error', 'No holdable lock tickets found.');
        }

        $reason = $validated['reason'] ?? 'Risk hold from Lock Ticket page';

        DB::transaction(function () use ($tickets, $reason) {
            OrderTicket::whereIn('id', $tickets->pluck('id'))
                ->update([
                    'risk_status' => 'hold',
                    'risk_reason' => $reason,
                    'risk_hold_at' => Carbon::now(),
                    'risk_hold_by' => auth()->id(),
                    'risk_release_at' => null,
                    'risk_release_by' => null,
                    'updated_at' => Carbon::now(),
                ]);
        });

        return back()->with('success', $tickets->count() . ' ticket(s) held successfully. Held tickets will not be counted in Probable Wins/Draw.');
    }

    public function release(Request $request)
    {
        $validated = $request->validate([
            'ticket_ids' => ['required', 'array', 'min:1'],
            'ticket_ids.*' => ['integer', 'exists:order_tickets,id'],
        ]);

        $tickets = OrderTicket::query()
            ->whereIn('id', $validated['ticket_ids'])
            ->where('risk_status', 'hold')
            ->get();

        if ($tickets->isEmpty()) {
            return back()->with('error', 'No held tickets found to release.');
        }

        DB::transaction(function () use ($tickets) {
            OrderTicket::whereIn('id', $tickets->pluck('id'))
                ->update([
                    'risk_status' => null,
                    'risk_reason' => null,
                    'risk_release_at' => Carbon::now(),
                    'risk_release_by' => auth()->id(),
                    'updated_at' => Carbon::now(),
                ]);
        });

        return back()->with('success', $tickets->count() . ' held ticket(s) released successfully.');
    }

    public function cancel(Request $request)
    {
        $validated = $request->validate([
            'ticket_ids' => ['required', 'array', 'min:1'],
            'ticket_ids.*' => ['integer', 'exists:order_tickets,id'],
        ]);

        $tickets = OrderTicket::query()
            ->with('order')
            ->whereIn('id', $validated['ticket_ids'])
            ->where('is_claimed', 0)
            ->where('is_winner', 0)
            ->whereHas('order', function ($query) {
                $query->where('status', 'Printed')
                    ->where('is_claimed', 0)
                    ->where('is_winner', 0);
            })
            ->get();

        if ($tickets->isEmpty()) {
            return back()->with('error', 'No cancellable lock tickets found.');
        }

        $orderIds = $tickets->pluck('order_id')->unique()->values();

        DB::transaction(function () use ($orderIds) {
            Order::whereIn('id', $orderIds)
                ->where('status', 'Printed')
                ->update([
                    'status' => 'Cancel',
                    'remarks' => 'Cancelled from Lock Ticket page',
                    'cancel_at' => Carbon::now(),
                    'cancel_approve_at' => Carbon::now(),
                    'cancel_approve_by' => auth()->id(),
                    'updated_at' => Carbon::now(),
                ]);
        });

        return back()->with('success', $orderIds->count() . ' locked invoice(s) cancelled successfully.');
    }


    public function applyRiskCap(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'draw_number' => ['nullable', 'string', 'max:255'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'cap_percent' => ['required', 'numeric', 'min:1', 'max:100'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $orders = Order::query()
            ->with('user.agent')
            ->where('status', 'Printed')
            ->where('is_claimed', 0)
            ->where('is_winner', 0)
            ->where('product_id', $validated['product_id'])
            ->where('draw_number', $validated['draw_number'] ?? null)
            ->where('user_id', $validated['user_id'])
            ->get();

        if ($orders->isEmpty()) {
            return back()->with('error', 'No printed order found for this lock group.');
        }

        $totalSale = (float) $orders->sum('total_price');
        $firstOrder = $orders->first();
        $commissionPercent = (float) ($firstOrder?->commission_percentage ?? $firstOrder?->user?->agent?->commission ?? 0);
        $commissionAmount = round(($totalSale * $commissionPercent) / 100, 2);
        $netSale = max(0, round($totalSale - $commissionAmount, 2));
        $capPercent = (float) $validated['cap_percent'];
        $maxPayable = round(($netSale * $capPercent) / 100, 2);

        RiskCapGroup::query()->updateOrCreate(
            [
                'product_id' => $validated['product_id'],
                'draw_number' => $validated['draw_number'] ?? null,
                'user_id' => $validated['user_id'],
            ],
            [
                'status' => 'capped',
                'cap_percent' => $capPercent,
                'total_sale' => $totalSale,
                'commission_percent' => $commissionPercent,
                'commission_amount' => $commissionAmount,
                'net_sale' => $netSale,
                'max_payable_amount' => $maxPayable,
                'reason' => $validated['reason'] ?? 'Risk payout cap from Lock Ticket page',
                'applied_by' => auth()->id(),
                'released_by' => null,
                'applied_at' => Carbon::now(),
                'released_at' => null,
            ]
        );

        return back()->with('success', 'Risk cap applied. Suspicious group payout will be limited to ' . number_format($maxPayable, 2) . ' without changing fixed prize amounts.');
    }

    public function releaseRiskCap(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'draw_number' => ['nullable', 'string', 'max:255'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $cap = RiskCapGroup::query()
            ->where('product_id', $validated['product_id'])
            ->where('draw_number', $validated['draw_number'] ?? null)
            ->where('user_id', $validated['user_id'])
            ->where('status', 'capped')
            ->first();

        if (!$cap) {
            return back()->with('error', 'No active risk cap found for this group.');
        }

        $cap->update([
            'status' => 'released',
            'released_by' => auth()->id(),
            'released_at' => Carbon::now(),
        ]);

        return back()->with('success', 'Risk cap released. This group will be calculated normally again.');
    }

}
