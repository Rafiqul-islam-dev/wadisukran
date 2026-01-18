<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\Order;

class CancelReportController extends Controller
{
    
public function cancelReport(Request $request)
{
    $query = Order::with(['user', 'product','tickets'])
                  ->where('status', 'Cancel'); // Match exact database value
    
    // User filter
    if ($request->user_id) {
        $query->where('user_id', $request->user_id);
    }
    
    // Date and time filter
    if ($request->date_from && $request->time_from) {
        $dateTimeFrom = $request->date_from . ' ' . $request->time_from;
        $query->where('created_at', '>=', $dateTimeFrom);
    } elseif ($request->date_from) {
        // If only date is provided, start from beginning of that day
        $query->whereDate('created_at', '>=', $request->date_from);
    }
    
    if ($request->date_to && $request->time_to) {
        $dateTimeTo = $request->date_to . ' ' . $request->time_to;
        $query->where('created_at', '<=', $dateTimeTo);
    } elseif ($request->date_to) {
        // If only date is provided, end at end of that day
        $query->whereDate('created_at', '<=', $request->date_to);
    }
    
    $orders = $query->latest()->get();
    
    // dd($orders);
    return Inertia::render('Report/CancelReport', [
        'orders' => $orders,
        'users' => User::all(),
        'filters' => $request->all()
    ]);
}
}
