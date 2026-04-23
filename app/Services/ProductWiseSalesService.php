<?php

namespace App\Services;

use App\Http\Resources\DailySummeryResource;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
class ProductWiseSalesService
{
    public function getUserDailySalesSummery($user_id = null, $from, $to)
{
    $user = $user_id ? User::find($user_id) : null;

    $fromDate = Carbon::parse($from)->startOfDay();
    $toDate = Carbon::parse($to)->endOfDay();

        $orders = Order::when($user, function ($q, $user) {
            $q->where('orders.user_id', $user->id);
        })
        ->where('orders.created_at', '>=', $fromDate)
        ->where('orders.created_at', '<=', $toDate)
        ->whereIn('orders.status', ['Printed', 'Cancel', 'Cancel-Request'])
        ->leftJoin('products', 'products.id', '=', 'orders.product_id')
        ->leftJoin('claims', function ($join) {
            $join->on('claims.invoice_no', '=', 'orders.invoice_no')
                 ->where('claims.claimed_by', '=', Auth::id());
        })
        ->selectRaw('
            orders.product_id,
            products.title as product_title,
            products.product_number as product_number,
            SUM(CASE WHEN orders.status = "Printed" THEN orders.commission ELSE 0 END) as total_commission,
            SUM(CASE WHEN orders.status IN ("Cancel", "Cancel-Request") THEN orders.total_price ELSE 0 END) as cancel_sell,
            SUM(CASE WHEN orders.status = "Printed" THEN orders.total_price ELSE 0 END) as total_sell,
            SUM(COALESCE(claims.amount, 0)) as total_prize_paid
        ')
        ->groupBy('orders.product_id', 'products.title', 'products.product_number')
        ->get();

    return [
        'name' => $user?->name,
        'address' => $user?->address,
        'from_date' => $fromDate->format('Y-m-d h:i:s'),
        'to_date' => $toDate->format('Y-m-d h:i:s'),
        'trn' => $user?->agent?->trn,
        'products' => DailySummeryResource::collection($orders),
        'total_sell' => (float) ($orders->sum('total_sell') + $orders->sum('cancel_sell')),
        'total_cancel_sell' => (float) $orders->sum('cancel_sell'),
        'total_net_sell' => (float) $orders->sum('total_sell'),
        'total_commission' => (float) $orders->sum('total_commission'),
        'total_prize_paid' => (float) $orders->sum('total_prize_paid'),
        'total_balance' => (float) ($orders->sum('total_sell') - ($orders->sum('total_prize_paid') + $orders->sum('total_commission'))),
    ];
}

}
