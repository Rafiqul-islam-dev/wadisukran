<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Services\CheckWinService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WinnerReportController extends Controller
{
    protected $checkWinService;

    public function __construct(CheckWinService $checkWinService)
    {
        $this->checkWinService = $checkWinService;
    }
    public function winnerReport(Request $request)
    {
        $wins = Order::where('is_winner', 1)
            ->where('is_claimed', 0)
            ->when($request->product_id, function ($q, $product) {
                $q->where('product_id', $product);
            })
            ->when($request->invoice_no, function ($q, $invoice) {
                $q->where('invoice_no', $invoice);
            })
            ->with(['user', 'product', 'user.agent', 'tickets'])
            ->latest()
            ->paginate(8);

        $wins->getCollection()->transform(function ($item) {
            $check_win = $this->checkWinService->checkWinOrderTicketsByInvoice($item->invoice_no);

            $item->check_win = $check_win;
            return $item;
        });

        $products = Product::active()->get();

        return Inertia::render('Report/WinnerReport', [
            'wins' => $wins,
            'products' => $products
        ]);
    }
}
