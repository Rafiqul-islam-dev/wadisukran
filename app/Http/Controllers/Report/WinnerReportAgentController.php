<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CheckWinService;
use Inertia\Inertia;
use Illuminate\Pagination\LengthAwarePaginator;

class WinnerReportAgentController extends Controller
{
    protected $checkWinService;

    public function __construct(CheckWinService $checkWinService)
    {
        $this->checkWinService = $checkWinService;
    }
public function winnerReportAgent(Request $request)
{
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
            'products' => $products,
            'filters' => ['agent' => '', 'from_date' => '', 'to_date' => ''],
        ]);
    }

    $request->validate([
        'agent'     => ['required', 'integer', 'exists:users,id'],
        'from_date' => ['nullable', 'date'],
        'to_date'   => ['nullable', 'date', 'after_or_equal:from_date'],
    ]);

    $q = Order::query()
        ->where('is_winner', 1)
        ->where('user_id', $request->agent)
        ->with(['user', 'product', 'user.agent', 'tickets'])
        ->latest();

    if ($request->filled('from_date')) $q->whereDate('created_at', '>=', $request->from_date);
    if ($request->filled('to_date'))   $q->whereDate('created_at', '<=', $request->to_date);

    $orders = $q->get();
    $totalPrize = 0.0;
    $claimedPrize = 0.0;

    foreach ($orders as $o) {
        $checkWin = $this->checkWinService->checkWinOrderTicketsByInvoice($o->invoice_no);
        $prize = (float)($checkWin['total_prize'] ?? ($checkWin->total_prize ?? 0));

        $totalPrize += $prize;

        if ((int)$o->is_claimed === 1) {
            $claimedPrize += $prize;
        }
    }

    $user = $orders->first()?->user ?? User::select('id','name','address')->find($request->agent);

    $row = (object)[
        'user_id' => (int)$request->agent,
        'user' => $user,
        'total_prize' => $totalPrize,
        'claimed_prize' => $claimedPrize,
        'unclaimed_prize' => $totalPrize - $claimedPrize,
    ];

    $wins = new LengthAwarePaginator([$row], 1, 8, 1, [
        'path' => $request->url(),
        'query' => $request->query(),
    ]);

    return Inertia::render('Report/WinnerReportAgent', [
        'wins' => $wins,
        'agents' => $agents,
        'products' => $products,
        'filters' => [
            'agent' => $request->agent,
            'from_date' => $request->from_date,
            'to_date' => $request->to_date,
        ],
    ]);
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
        return $item;
    });

    return response()->json($list);
}

}
