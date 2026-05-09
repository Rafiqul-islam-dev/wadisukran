<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\CompannySetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\Order;
use Carbon\Carbon;

class CancelRequestController extends Controller
{

public function cancelRequest(Request $request)
{
    $query = Order::with(['user', 'product', 'tickets'])
        ->where('status', 'Cancel-Request');

    if ($request->user_id) {
        $query->where('user_id', $request->user_id);
    }
    if ($request->invoice_no) {
        $query->where('invoice_no', 'like', '%'.$request->invoice_no.'%');
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
        $order->formatted_cancel_time = $order->cancel_at ? Carbon::parse($order->cancel_at)->format('h:i A') : 'N/A';
        return $order;
    });


    // dd($orders);
    return Inertia::render('Report/CancelRequest', [
        'orders' => $orders,
        'users' => User::all(),
        'filters' => $request->all(),
        'company' => $company
    ]);
}


public function acceptCancel(Order $order)
{
    // dd($order);

    $order->update([
        'status' => 'Cancel',
        'cancel_approve_at' => Carbon::now()->format('Y-m-d H:i:s'),
        'cancel_approve_by' => auth()->id()
    ]);

    return back()->with('success', 'Order cancelled successfully.');
}

}
