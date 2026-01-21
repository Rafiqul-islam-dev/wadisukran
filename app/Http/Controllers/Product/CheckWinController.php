<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\OrderTicket;
use App\Services\CheckWinService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CheckWinController extends Controller
{
    protected $checkWinService;

    public function __construct(CheckWinService $checkWinService)
    {
        $this->checkWinService = $checkWinService;
    }

    public function index()
    {
        $wins = OrderTicket::get();
        return Inertia::render('CheckWin/Index');
    }

    public function check_win(Request $request)
    {
        $request->validate([
            'invoice_no' => 'required|string|exists:orders,invoice_no'
        ]);
        $summery = $this->checkWinService->CheckWinByInvoice($request->invoice_no);
        return Inertia::render('CheckWin/Index',[
            'summery' => $summery
        ]);
    }
}
