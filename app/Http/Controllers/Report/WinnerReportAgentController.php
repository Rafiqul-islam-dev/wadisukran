<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\CompannySetting;
use App\Services\CheckWinService;
use Inertia\Inertia;
use Illuminate\Pagination\LengthAwarePaginator;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class WinnerReportAgentController extends Controller
{
    protected $checkWinService;

    public function __construct(CheckWinService $checkWinService)
    {
        $this->checkWinService = $checkWinService;
    }
    public function winnerReportAgent(Request $request)
    {
        $all_agents = User::where('user_type', 'agent')->get();

        $agents = User::where('user_type', 'agent')
            ->where('status', 'active')
            ->select('id', 'name')
            ->get();

        $products = Product::active()->get();

        $hasSearch = $request->filled('agent') || $request->filled('from_date') || $request->filled('to_date');

        if (!$hasSearch) {
            $wins = new LengthAwarePaginator([], 0, 8, 1, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);

            return Inertia::render('Report/WinnerReportAgent', [
                'wins' => $wins,
                'agents' => $agents,
                'all_agents' => $all_agents,
                'products' => $products,
                'filters' => ['agent' => '', 'from_date' => '', 'to_date' => ''],
            ]);
        }

        $request->validate([
            'agent'     => ['nullable', 'integer', 'exists:users,id'],
            'from_date' => ['nullable', 'date'],
            'to_date'   => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

       // Get all agents first (apply filter if needed)
        $agents = User::query()->where('user_type', 'agent')
                      ->when($request->filled('agent'), function($q, $agent){
                        $q->where('id', $agent);
                      })->get();

        // Prepare summary array
        $agentSummary = [];

        // Preload orders with necessary filters
        $orders = Order::query()
            ->where('is_winner', 1)
            ->when($request->from_date, fn($q) => $q->whereDate('created_at', '>=', $request->from_date))
            ->when($request->to_date, fn($q) => $q->whereDate('created_at', '<=', $request->to_date))
            ->get()
            ->groupBy('user_id'); // group orders by agent's user_id

        foreach ($agents as $agent) {
            $agentOrders = $orders[$agent->id] ?? []; // get orders for this agent or empty
            $totalPrize = $claimedPrize = $unclaimedPrize = 0;

            foreach ($agentOrders as $order) {
                $checkWin = $this->checkWinService->checkWinOrderTicketsByInvoice($order->invoice_no);
                $prize = (float)($checkWin['total_prize'] ?? ($checkWin->total_prize ?? 0));

                $totalPrize += $prize;

                if ($order->is_claimed) {
                    $claimedPrize += $prize;
                } else {
                    $unclaimedPrize += $prize;
                }
            }

            $agentSummary[] = [
                'user_id' => $agent->id,
                'user' => $agent,
                'total_prize' => $totalPrize,
                'claimed_prize' => $claimedPrize,
                'unclaimed_prize' => $unclaimedPrize,
            ];
        }

        // Pagination
        $perPage = 10;
        $page = request()->get('page', 1);

        $wins = new \Illuminate\Pagination\LengthAwarePaginator(
            array_slice($agentSummary, ($page - 1) * $perPage, $perPage),
            count($agentSummary),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return Inertia::render('Report/WinnerReportAgent', [
            'wins' => $wins,
            'agents' => $agents,
            'all_agents' => $all_agents,
            'products' => $products,
            'filters' => [
                'agent' => $request->agent,
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
            ]
        ]);
    }

    public function agentReportAgent(Request $request)
    {
        $request->validate([
            'agent'     => ['nullable', 'integer', 'exists:users,id'],
            'from_date' => ['nullable', 'date'],
            'to_date'   => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        $agents = User::query()->where('user_type', 'agent')
                    ->when($request->filled('agent'), function($q, $agent){
                    $q->where('id', $agent);
                    })->get();

        $agentSummary = [];
        $orders = Order::query()
            ->where('is_winner', 1)
            ->when($request->from_date, fn($q) => $q->whereDate('created_at', '>=', $request->from_date))
            ->when($request->to_date, fn($q) => $q->whereDate('created_at', '<=', $request->to_date))
            ->get()
            ->groupBy('user_id');

        foreach ($agents as $agent) {
            $agentOrders = $orders[$agent->id] ?? [];
            $totalPrize = $claimedPrize = $unclaimedPrize = 0;

            foreach ($agentOrders as $order) {
                $checkWin = $this->checkWinService->checkWinOrderTicketsByInvoice($order->invoice_no);
                $prize = (float)($checkWin['total_prize'] ?? ($checkWin->total_prize ?? 0));

                $totalPrize += $prize;

                if ($order->is_claimed) {
                    $claimedPrize += $prize;
                } else {
                    $unclaimedPrize += $prize;
                }
            }

            $agentSummary[] = [
                'user_id' => $agent->id,
                'user' => $agent,
                'total_prize' => $totalPrize,
                'claimed_prize' => $claimedPrize,
                'unclaimed_prize' => $unclaimedPrize,
            ];
        }

        $company = CompannySetting::firstOrFail();

        $pdf = Pdf::loadView('pdf.agent_report', [
            'wins' => $agentSummary,
            'company' => $company,
            'from_date' => Carbon::parse($request->from_date)->startOfDay(),
            'to_date' => Carbon::parse($request->to_date)->endOfDay()
        ]);

        return $pdf->download('agent_report_' . ($agent->name ?? 'report') . '_' . Carbon::now()->format('Y-m-d_H-i-s') . '.pdf');
    }

    public function winnerReportAgentDetails(Request $request)
    {
        $request->validate([
            'agent'     => ['required', 'integer', 'exists:users,id'],
            'from_date' => ['nullable', 'date'],
            'to_date'   => ['nullable', 'date', 'after_or_equal:from_date'],
            'claimed'   => ['nullable', 'in:0,1'],
        ]);

        $q = Order::query()
            ->where('is_winner', 1)
            ->where('user_id', $request->agent)
            ->with(['user', 'product', 'user.agent', 'tickets'])
            ->latest();

        if ($request->filled('from_date')) $q->whereDate('created_at', '>=', $request->from_date);
        if ($request->filled('to_date'))   $q->whereDate('created_at', '<=', $request->to_date);

        if ($request->filled('claimed')) {
            $q->where('is_claimed', (int)$request->claimed);
        }

        $list = $q->paginate(10)->withQueryString();

        $list->getCollection()->transform(function ($item) {
            $checkWin = $this->checkWinService->checkWinOrderTicketsByInvoice($item->invoice_no);
            $item->check_win = $checkWin;
            $item->claim_user = $item->claim?->claim_user?->name;
            $item->claimed_at = $item->claim?->created_at;
            return $item;
        });

        return response()->json($list);
    }

    public function agentReportAgentDetailsPDF(Request $request){
        $request->validate([
            'agent'     => ['required', 'integer', 'exists:users,id'],
            'from_date' => ['nullable', 'date'],
            'to_date'   => ['nullable', 'date', 'after_or_equal:from_date'],
            'claimed'   => ['nullable', 'in:0,1'],
        ]);

        $q = Order::query()
            ->where('is_winner', 1)
            ->where('user_id', $request->agent)
            ->with(['user', 'product', 'user.agent', 'tickets'])
            ->latest();

        if ($request->filled('from_date')) $q->whereDate('created_at', '>=', $request->from_date);
        if ($request->filled('to_date'))   $q->whereDate('created_at', '<=', $request->to_date);

        if ($request->filled('claimed')) {
            $q->where('is_claimed', (int)$request->claimed);
        }

        $lists = $q->get()->map(function($list){
            $checkWin = $this->checkWinService->checkWinOrderTicketsByInvoice($list->invoice_no);
            $list->check_win = $checkWin;
            $list->claim_user = $list->claim?->claim_user?->name;
            $list->claimed_at = $list->claim?->created_at;
            return $list;
        });

        // return $lists;
        $company = CompannySetting::firstOrFail();
        $agent = User::find($request->agent);

        $pdf = Pdf::loadView('pdf.agent_report_details', [
            'lists' => $lists,
            'company' => $company,
            'agent' => $agent,
            'from_date' => Carbon::parse($request->from_date)->startOfDay(),
            'to_date' => Carbon::parse($request->to_date)->endOfDay(),
            'claimed' => $request->claimed,
        ]);

        return $pdf->download('agent_report_details_' . ($request->agent) . '_' . Carbon::now()->format('Y-m-d_H-i-s') . '.pdf');

    }
}
