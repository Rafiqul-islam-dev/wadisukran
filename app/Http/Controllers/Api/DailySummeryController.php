<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DailySummeryResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DailySummeryController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->input('from_date'); // YYYY-MM-DD
        $to   = $request->input('to_date');   // YYYY-MM-DD

        $orders = Order::where('orders.user_id', Auth::id())
            ->when($from && $to, function ($q) use ($from, $to) {
                $q->whereBetween('orders.created_at', [$from, $to]);
            })
            ->where('orders.status', '!=', 'Pending')
            ->leftJoin('products', 'products.id', '=', 'orders.product_id')
            ->selectRaw('
                orders.product_id,
                products.title as product_title,
                SUM(CASE WHEN orders.status = "Printed" THEN orders.total_price ELSE 0 END) as total_price,
                SUM(CASE WHEN orders.status = "Printed" THEN orders.commission ELSE 0 END) as total_commission,
                COUNT(CASE WHEN orders.status = "Cancel" THEN 1 END) as cancel_orders,
                0 as total_prize_paid
            ')
            ->groupBy('orders.product_id', 'products.title')
            ->get();
        return response()->json([
            'name' => Auth::user()->name,
            'address' => Auth::user()->address,
            'from_date' => $from,
            'to_date' => $to,
            'trn' => Auth::user()->agent?->trn,
            'products' => DailySummeryResource::collection($orders),
            'total_sell' => (float) $orders->sum('total_price'),
            'total_commission' => (float) $orders->sum('total_commission'),
            'total_prize_paid' => (float) $orders->sum('total_prize_paid'),
            'total_cancel_orders' => (int) $orders->sum('cancel_orders')
        ]);
    }
}
