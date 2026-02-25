<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CheckWinService;
use Inertia\Inertia;

class WinnerReportAgentController extends Controller
{
    protected $checkWinService;

    public function __construct(CheckWinService $checkWinService)
    {
        $this->checkWinService = $checkWinService;
    }
    public function winnerReportAgent(Request $request)
    {
        $agents = User::where('user_type', 'agent')->where('status', 'active')->get();
        $wins = Order::where('is_winner', 1)
            
            ->with(['user', 'product', 'user.agent', 'tickets'])
            ->latest()
            ->paginate(8);
        
        // dd($wins);

        $wins->getCollection()->transform(function ($item) {
            $check_win = $this->checkWinService->checkWinOrderTicketsByInvoice($item->invoice_no);

            $item->check_win = $check_win;
            return $item;
        });

        $products = Product::active()->get();

        return Inertia::render('Report/WinnerReportAgent', [
            'wins' => $wins,
            'agents' => $agents,
            'products' => $products
        ]);
    }
}
