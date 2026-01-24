<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderTicket;
use App\Models\Product;
use App\Services\CheckWinService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
                ->when($request->invoice_no, function($q, $invoice){
                    $q->where('invoice_no', $invoice);
                })
                ->with(['user', 'product', 'user.agent', 'tickets'])
                ->latest()
                ->paginate(5);

        $wins->getCollection()->transform(function ($item) {
            $check_win = $this->checkWinService->checkWinOrderTicketsByInvoice($item->invoice_no);

            $item->check_win = $check_win;
            return $item;
        });

        $products = Product::active()->get();
        return Inertia::render('CheckWin/Index', [
            'wins' => $wins,
            'products' => $products
        ]);
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
        if ($summery) {
            return response()->json([
                'success' => true,
                'message' => 'Your invoice has been successfully checked and you won the prize.',
                'summery' => $summery
            ], 200);
        } else {
            return response()->json([
                'success' => true,
                'message' => 'Your invoice has been successfully checked but you did not won any prize.',
            ], 200);
        }
    }

    public function claim_win(Request $request)
    {
        $request->validate([
            'invoice_no' => 'required|string|exists:orders,invoice_no'
        ]);
        $order = Order::where('invoice_no', $request->invoice_no)->first();
        if ($order->is_claimed === 0) {
            $claim_msg =  $this->checkWinService->ClaimWin($request->invoice_no);
            return response()->json([
                'success' => true,
                'message' => $claim_msg
            ]);
        } else {
            return 'This invoice already claimed';
        }
    }
}
