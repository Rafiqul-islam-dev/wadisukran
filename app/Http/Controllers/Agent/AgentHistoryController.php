<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AgentHistoryController extends Controller
{
    public function index(){
        $users = User::whereHas('agent')->with('agent')->withSum('sales', 'total_price')->paginate(10);
        return Inertia::render(('Agent/AgentHistory/Index'), [
            'users' => $users
        ]);
    }
}
