<?php

namespace App\Services;

use App\Models\AgentAccount;
use Illuminate\Support\Facades\Auth;

class AgentAccountService
{

    public function store(array $data)
    {
        $old_balance = 0;
        $current_balance = 0;

        $last_posting = AgentAccount::where('type', 'posting')->latest()->first();
        if ($last_posting) {
            $old_balance = $last_posting->current_balance;
        }

        if ($data['type'] === 'posting') {
            $fromDate = $last_posting?->created_at;

            $total_commission = AgentAccount::where('user_id', $data['user_id'])
                ->whereIn('type', ['commission', 'claim'])
                ->when($fromDate, function ($q) use ($fromDate) {
                    $q->where('created_at', '>', $fromDate);
                })
                ->sum('amount');

            if ($old_balance > $total_commission) {
                $total_payable = $old_balance - $total_commission;
            } else {
                $total_payable = $total_commission - $old_balance;
            }
            $current_balance = $total_payable - $data['amount'];
        }
        AgentAccount::create([
            'user_id' => $data['user_id'],
            'type' => $data['type'],
            'amount' => $data['amount'],
            'old_balance' => $old_balance,
            'current_balance' => $current_balance,
            'order_id' => $data['order_id'] ?? null,
            'created_by' => Auth::id()
        ]);
    }
}
