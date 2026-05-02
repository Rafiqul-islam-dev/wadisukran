<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\CompannySetting;
use App\Models\Order;
use App\Models\Product;
use App\Services\CheckWinService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
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
            ->when($request->from_date, function ($q, $from_date) {
                $q->whereDate('created_at', '>=', $from_date);
            })
            ->when($request->to_date, function ($q, $to_date) {
                $q->whereDate('created_at', '<=', $to_date);
            })
            ->with(['user', 'product', 'user.agent', 'tickets'])
            ->latest()
            ->paginate(8);

        $filteredCollection = $wins->getCollection()->map(function ($item) {
            $check_win = $this->checkWinService->checkWinOrderTicketsByInvoice($item->invoice_no);
            $item->check_win = $check_win;
            return $item;
        })->filter(function ($item) {
            return isset($item->check_win['total_prize']) && $item->check_win['total_prize'] > 0;
        })->values();

        $wins->setCollection($filteredCollection);

        $products = Product::active()->get();

        return Inertia::render('Report/WinnerReport', [
            'wins' => $wins,
            'products' => $products,
            'filters' => $request->only(['product_id', 'invoice_no', 'from_date', 'to_date'])
        ]);
    }

    public function winnerReportPdf(Request $request)
    {
        $wins = Order::where('is_winner', 1)
            ->where('is_claimed', 0)
            ->when($request->product_id, function ($q, $product) {
                $q->where('product_id', $product);
            })
            ->when($request->invoice_no, function ($q, $invoice) {
                $q->where('invoice_no', $invoice);
            })
            ->when($request->from_date, function ($q, $from_date) {
                $q->whereDate('created_at', '>=', $from_date);
            })
            ->when($request->to_date, function ($q, $to_date) {
                $q->whereDate('created_at', '<=', $to_date);
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

        $pdf = Pdf::loadView('pdf.winner_report', [
            'wins' => $wins,
            'company' => $company,
            'from_date' => $request->from_date ?? '-',
            'to_date' => $request->to_date ?? '-'
        ]);

        return $pdf->download('winner_report_' . Carbon::now()->format('Y-m-d_H-i-s') . '.pdf');
    }
}
