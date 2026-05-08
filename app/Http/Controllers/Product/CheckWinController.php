<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\CompannySetting;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CheckWinService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CheckWinController extends Controller
{
    protected $checkWinService;

    public function __construct(CheckWinService $checkWinService)
    {
        $this->checkWinService = $checkWinService;
    }

    public function index(Request $request)
    {
        $wins = Order::where('is_winner', 1)
                ->when($request->product_id, function($q, $product){
                    $q->where('product_id', $product);
                })
                ->when($request->user_id, function($q, $userId){
                    $q->where('user_id', $userId);
                })
                ->when($request->from_date, function ($q, $from_date) {
                    $q->whereDate('created_at', '>=', $from_date);
                })
                ->when($request->to_date, function ($q, $to_date) {
                    $q->whereDate('created_at', '<=', $to_date);
                })
                ->when($request->from_time, function ($q, $from_time) use ($request) {
                    if ($request->from_date) {
                        $q->whereTime('created_at', '>=', $from_time);
                    }
                })
                ->when($request->to_time, function ($q, $to_time) use ($request) {
                    if ($request->to_date) {
                        $q->whereTime('created_at', '<=', $to_time);
                    }
                })
                ->with(['user', 'product', 'user.agent', 'tickets'])
                ->latest()
                ->paginate(10);

        $filteredCollection = $wins->getCollection()->map(function ($item) {
            $check_win = $this->checkWinService->checkWinOrderTicketsByInvoice($item->invoice_no);
            $item->check_win = $check_win;
            return $item;
        })->filter(function ($item) {
            return isset($item->check_win['total_prize']) && $item->check_win['total_prize'] > 0;
        })->values();

        $wins->setCollection($filteredCollection);

        $products = Product::active()->get();

        $agents = User::whereHas('agent')->select('id', 'name')->get();

        $totalPrice = $wins->getCollection()->sum(fn ($item) => $item->check_win['total_prize'] ?? 0);

        return Inertia::render('CheckWin/Index', [
            'wins' => $wins,
            'products' => $products,
            'agents' => $agents,
            'filters' => $request->only(['product_id', 'user_id', 'from_date', 'to_date', 'from_time', 'to_time']),
            'totalPrice' => $totalPrice
        ]);
    }

    public function checkWinsPdf(Request $request)
    {
        $wins = Order::where('is_winner', 1)
                ->when($request->product_id, function($q, $product){
                    $q->where('product_id', $product);
                })
                ->when($request->user_id, function($q, $userId){
                    $q->where('user_id', $userId);
                })
                ->when($request->from_date, function ($q, $from_date) {
                    $q->whereDate('created_at', '>=', $from_date);
                })
                ->when($request->to_date, function ($q, $to_date) {
                    $q->whereDate('created_at', '<=', $to_date);
                })
                ->when($request->from_time, function ($q, $from_time) use ($request) {
                    if ($request->from_date) {
                        $q->whereTime('created_at', '>=', $from_time);
                    }
                })
                ->when($request->to_time, function ($q, $to_time) use ($request) {
                    if ($request->to_date) {
                        $q->whereTime('created_at', '<=', $to_time);
                    }
                })
                ->with(['user', 'product', 'user.agent', 'tickets'])
                ->latest()
                ->get();

        $wins = $wins->map(function ($item) {
            $check_win = $this->checkWinService->checkWinOrderTicketsByInvoice($item->invoice_no);
            $item->check_win = $check_win;
            return $item;
        })->filter(function ($item) {
            return isset($item->check_win['total_prize']) && $item->check_win['total_prize'] > 0;
        })->values();

        $company = CompannySetting::firstOrFail();

        $totalPrice = $wins->sum(fn ($item) => $item->check_win['total_prize'] ?? 0);

        $fromDateTime = $request->from_date ? ($request->from_time ? $request->from_date . ' ' . $request->from_time : $request->from_date) : '-';
        $toDateTime = $request->to_date ? ($request->to_time ? $request->to_date . ' ' . $request->to_time : $request->to_date) : '-';

        $agentName = $request->user_id ? User::find($request->user_id)?->name : null;

        $pdf = Pdf::loadView('pdf.check_wins_report', [
            'wins' => $wins,
            'company' => $company,
            'from_date' => $fromDateTime,
            'to_date' => $toDateTime,
            'totalPrice' => $totalPrice,
            'agentName' => $agentName
        ]);

        return $pdf->stream('check_wins_report_' . Carbon::now()->format('Y-m-d_H-i-s') . '.pdf');
    }

    public function check_win(Request $request)
    {
        $request->validate([
            'invoice_no' => 'required|string|exists:orders,invoice_no'
        ]);
        $order = Order::where('invoice_no', $request->invoice_no)->first();
        if ($order->is_claimed == 1) {
            return response()->json([
                'success' => false,
                'message' => 'This invoice already claimed.'
            ], 200);
        }

        $summery = $this->checkWinService->CheckWinByInvoice($request->invoice_no);
        if ($summery['summery']) {
            return response()->json([
                'success' => true,
                'message' => 'Your invoice has been successfully checked and you won the prize.',
                'summery' => $summery
            ], 200);
        } else {
            if($order->product->draw_type === 'once'){
                $summery = $this->checkWinService->CheckWinByInvoiceOnce($request->invoice_no);
                if ($summery['summery']) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Your invoice has been successfully checked and you won the prize.',
                        'summery' => $summery
                    ], 200);
                }
                else{
                    return response()->json([
                        'success' => true,
                        'message' => 'Your invoice has been successfully checked but you did not won any prize.',
                    ], 200);
                }
            }
            else{
                return response()->json([
                    'success' => true,
                    'message' => 'Your invoice has been successfully checked but you did not won any prize.',
                ], 200);
            }
        }
    }

    public function claim_win(Request $request)
    {
        $request->validate([
            'invoice_no' => 'required|string|exists:orders,invoice_no'
        ]);
        $order = Order::where('invoice_no', $request->invoice_no)->where('status', 'Printed')->first();
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid invoice no.'
             ], 400);
        }
        if ($order->is_claimed === 0) {
            $claim_msg =  $this->checkWinService->ClaimWin($request->invoice_no);
            return response()->json([
                'success' => true,
                'message' => $claim_msg
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'This invoice already claimed'
            ], 409);
        }
    }

}
