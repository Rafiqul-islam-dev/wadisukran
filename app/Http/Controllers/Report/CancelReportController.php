<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\CompannySetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\Order;

class CancelReportController extends Controller
{

public function cancelReport(Request $request)
{
    $query = Order::with(['user', 'product', 'tickets', 'cancelApprove'])
        ->where('status', 'Cancel');

    if ($request->user_id) {
        $query->where('user_id', $request->user_id);
    }

    if ($request->date_from && $request->time_from) {
        $dateTimeFrom = $request->date_from . ' ' . $request->time_from;
        $query->where('created_at', '>=', $dateTimeFrom);
    } elseif ($request->date_from) {
        $query->whereDate('created_at', '>=', $request->date_from);
    }

    if ($request->date_to && $request->time_to) {
        $dateTimeTo = $request->date_to . ' ' . $request->time_to;
        $query->where('created_at', '<=', $dateTimeTo);
    } elseif ($request->date_to) {
        $query->whereDate('created_at', '<=', $request->date_to);
    }

    $company = CompannySetting::firstOrFail();
    $orders = $query->latest()->get()->map(function ($order) {
        $order->formatted_created_at = $order->created_at->format('d M, Y h:i:s A');
        $order->formatted_cancel_approve_time = $order->cancel_approve_at ? \Carbon\Carbon::parse($order->cancel_approve_at)->format('h:i A') : 'N/A';
        $order->formatted_sales_date = $order->sales_date ? \Carbon\Carbon::parse($order->sales_date)->format('d M, Y') : 'N/A';
        if ($order->product) {
            $order->product->formatted_draw_date = $order->product->draw_date ? \Carbon\Carbon::parse($order->product->draw_date)->format('d M, Y') : 'N/A';
        }
        return $order;
    });

    return Inertia::render('Report/CancelReport', [
        'orders' => $orders,
        'users' => User::all(),
        'filters' => $request->all(),
        'company' => $company
    ]);
}
}
