<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\AgentService;
use App\Services\CustomerService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public $customerService;
    public $agentService;
    public function __construct(CustomerService $customerService, AgentService $agentService)
    {
        $this->customerService = $customerService;
        $this->agentService = $agentService;
    }

    public function index()
    {
        $agent_count = User::where('user_type', 'agent')->whereHas('agent')->count();
        $today_sales = Order::where('status', 'Printed')
                        ->when(!Auth::user()->hasAnyRole(['Super Admin', 'Moderator']), function ($query) {
                            $query->where('user_id', Auth::id());
                        })
                        ->whereDate('created_at', Carbon::today())
                        ->sum('total_price');

        $today_commissions = Order::where('status', 'Printed')
                        ->when(!Auth::user()->hasAnyRole(['Super Admin', 'Moderator']), function ($query) {
                            $query->where('user_id', Auth::id());
                        })
                        ->whereDate('created_at', Carbon::today())->sum('commission');
        $top_ten_customers = $this->customerService->topTen();
        $top_ten_agents = $this->agentService->topTen();
        // return $top_ten_customers;
        return Inertia::render('Dashboard', [
            'agent_count' => number_format($agent_count),
            'today_sales' => number_format($today_sales),
            'today_commissions' => number_format($today_commissions),
            'top_ten_customers' => $top_ten_customers,
            'top_ten_agents' => $top_ten_agents,
        ]);
    }
}
