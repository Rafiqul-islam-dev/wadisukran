<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CheckWinService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CheckWinnerController extends Controller
{
    protected $checkWinService;

    public function __construct(CheckWinService $checkWinService)
    {
        $this->checkWinService = $checkWinService;
    }

    public function checkWin(Request $request)
    {
        $order = Order::where('invoice_no', $request->invoice_no)->where('status', 'Printed')->first();
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

        $checkTime = $this->checkWinService->checkAndClaimAvailability($order);
        if (!$checkTime) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, you cannot check or claim this invoice now. Please try after draw.'
            ], 403);
        }

        $summery = $this->checkWinService->CheckWinByInvoice($request->invoice_no);
        if ($summery && $summery['total_prize'] > 0) {
            if ($summery['total_prize'] >= company_setting()?->max_win_amount) {
                return response()->json([
                    'success' => true,
                    'message' => 'Congratulations this invoice won. To claim the prize please contact with support team.'
                ], 200);
            } else {
                return response()->json([
                    'success' => true,
                    'message' => 'Congratulations this invoice won. and you can claim.',
                    'data' => $summery['summery'],
                    'total_prize' => $summery['total_prize']
                ], 200);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, this invoice did not win any prize.',
            ], 200);
        }
    }

    public function claimWin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invoice_no' => [
                'required',
                'string',
                Rule::exists('orders', 'invoice_no')->where(function ($q) {
                    $q->where('status', 'Printed');
                }),
            ],
        ]);
        $order = Order::where('invoice_no', $request->invoice_no)->where('status', 'Printed')->first();
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 400);
        }
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid invoice no.'
            ], 400);
        }

        $checkTime = $this->checkWinService->checkAndClaimAvailability($order);
        if (!$checkTime) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, you cannot check or claim this invoice now. Please try after draw.'
            ], 403);
        }

        $summery = $this->checkWinService->CheckWinByInvoice($request->invoice_no);
        if (!$summery || $summery['total_prize'] <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, this invoice did not win any prize.',
            ], 200);
        }

        if ($summery['total_prize'] >= company_setting()?->max_win_amount) {
            return response()->json([
                'success' => true,
                'message' => 'Congratulations this invoice won. To claim the prize please contact with support team.'
            ], 200);
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
