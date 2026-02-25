<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\AgentAccount;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AccountsSummeryController extends Controller
{
    public function index(Request $request){
        $users = User::where('user_type', 'agent')->where('status', 'active')->get();

        $agents = [];
        foreach($users as $user){
            $last_posting = AgentAccount::where('user_id', $user->id)->where('type', 'posting')->latest()->first();
            $account = AgentAccount::where('user_id', $user->id)
                ->when($last_posting?->created_at, function ($q) use ($last_posting) {
                    $q->where('created_at', '>', $last_posting->created_at);
                })
                ->select('type', DB::raw('SUM(amount) as total_amount'))
                ->groupBy('type')
                ->get()
                ->pluck('total_amount', 'type');

            $agents[] = [
                'name' => $user->name,
                'address' => $user->address,
                'total_sell' => !empty($account['sell']) ? $account['sell'] : 0,
                'total_commission' => !empty($account['commission']) ? $account['commission'] : 0,
                'total_win' => !empty($account['win']) ? $account['win'] : 0,
                'total_claim' => !empty($account['claim']) ? $account['claim'] : 0,
                'old_due' => !empty($last_posting->old_due) ? 500.00 : 0,
            ];
        }

        return Inertia::render('Accounts/Summery', [
            'users' => $agents
        ]);
    }
}
