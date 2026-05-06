<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderTicket;
use App\Models\Product;
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

        return Inertia::render('Orders/LockTickets', [
            'lockedTickets' => $lockedTickets,
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

        return back()->with('success', $orderIds->count() . ' locked invoice cancelled successfully.');
    }
}
