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

        $orders = Order::where(function($q) use ($fromDate, $toDate, $user) {
                $q->where(function($q1) use ($fromDate, $toDate, $user) {
                    $q1->whereBetween('orders.created_at', [$fromDate, $toDate]);
                    if ($user) {
                        $q1->where('orders.user_id', $user->id);
                    }
                })
                ->orWhereHas('claim', function($q2) use ($fromDate, $toDate, $user) {
                    $q2->whereBetween('created_at', [$fromDate, $toDate]);
                    if ($user) {
                        $q2->where('claimed_by', $user->id);
                    }
                });
            })
            ->leftJoin('products', 'products.id', '=', 'orders.product_id')
            ->leftJoin('claims', 'claims.invoice_no', '=', 'orders.invoice_no')
            ->selectRaw('
                orders.product_id,
                products.title as product_title,
                products.product_number as product_number,
                SUM(CASE WHEN orders.created_at BETWEEN ? AND ? AND orders.status = "Printed" ' . ($user ? 'AND orders.user_id = '.$user->id : '') . ' THEN orders.commission ELSE 0 END) as total_commission,
                SUM(CASE WHEN orders.status IN ("Cancel", "Cancel-Request") AND orders.created_at BETWEEN ? AND ? ' . ($user ? 'AND orders.user_id = '.$user->id : '') . ' THEN orders.total_price ELSE 0 END) as cancel_sell,
                SUM(CASE WHEN orders.status = "Printed" AND orders.created_at BETWEEN ? AND ? ' . ($user ? 'AND orders.user_id = '.$user->id : '') . ' THEN orders.total_price ELSE 0 END) as total_sell,
                SUM(CASE WHEN claims.created_at BETWEEN ? AND ? ' . ($user ? 'AND claims.claimed_by = '.$user->id : '') . ' THEN COALESCE(claims.amount, 0) ELSE 0 END) as total_prize_paid
            ', [$fromDate, $toDate, $fromDate, $toDate, $fromDate, $toDate, $fromDate, $toDate])
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
