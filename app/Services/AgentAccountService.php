<?php

namespace App\Services;

use App\Models\AgentAccount;
use Illuminate\Support\Facades\Auth;

class AgentAccountService{

    public function Credit(array $data):string
    {
        AgentAccount::create([
            'user_id' => $data['user_id'],
            'type' => 'credit',
            'transaction_type' => $data['transaction_type'],
            'amount' => $data['amount'],
            'created_by' => Auth::id()
        ]);

        return 'Account credited successfully.';
    }

    public function Debit(array $data):string
    {
        $last_debit = AgentAccount::where('user_id', $data['user_id'])->where('type', 'debit')->latest()->first();
        $old_balance = $last_debit?->current_balance ?? 0;

        $fromDate = $last_debit?->created_at;

        $total_commission = AgentAccount::where('user_id', $data['user_id'])
            ->where('type', 'credit')
            ->when($fromDate, function ($q) use ($fromDate) {
                $q->where('created_at', '>', $fromDate);
            })
            ->sum('amount');

        $total_payable = $total_commission - $old_balance;
        $current_balance = $total_payable - (int) $data['amount'];
        AgentAccount::create([
            'user_id' => $data['user_id'],
            'type' => 'debit',
            'transaction_type' => $data['transaction_type'],
            'amount' => $data['amount'],
            'old_balance' => $current_balance,
            'current_balance' => $current_balance ,
            'created_by' => Auth::id()
        ]);
        return 'Account debited sucessfully.';
    }
}
