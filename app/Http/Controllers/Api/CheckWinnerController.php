<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CheckWinService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckWinnerController extends Controller
{
    protected $checkWinService;

    public function __construct(CheckWinService $checkWinService)
    {
        $this->checkWinService = $checkWinService;
    }

    public function checkWin(Request $request)
    {
        $order = Order::where('invoice_no', $request->invoice_no)->where('user_id', Auth::id())->first();
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid invoice no.'
            ], 400);
        }
        if ($order->is_claimed == 1) {
            return response()->json([
                'success' => false,
                'message' => 'This invoice already claimed.'
            ], 409);
        }
        $summery = $this->checkWinService->CheckWinByInvoice($request->invoice_no);
        if ($summery) {
            return response()->json([
                'success' => true,
                'message' => 'Congratulations this invoice won. and you can claim.',
                'data' => $summery
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, this invoice did not win any prize.',
            ], 200);
        }
    }
}
