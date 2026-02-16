<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $agent_count = User::where('user_type', 'agent')->whereHas('agent')->count();
        $today_sales = Order::where('status', 'Printed')->whereDate('created_at', Carbon::today())->sum('total_price');
        $today_commissions = Order::where('status', 'Printed')->whereDate('created_at', Carbon::today())->sum('commission');
        return Inertia::render('Dashboard', [
            'agent_count' => number_format($agent_count),
            'today_sales' => number_format($today_sales),
            'today_commissions' => number_format($today_commissions),
        ]);
    }
}
