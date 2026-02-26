<?php

namespace App\Services;

use App\Models\AgentAccount;
use Illuminate\Support\Facades\Auth;

class AgentAccountService
{

    public function store(array $data)
    {
        $old_due = 0;
        $current_due = 0;

        if ($data['type'] === 'posting') {
            $last_posting = AgentAccount::where('user_id', $data['user_id'])->where('type', 'posting')->where('amount', '>', 0)->latest()->first();
            if ($last_posting) {
                $old_due = $last_posting->old_due;
            }
            $fromDate = $last_posting?->created_at;

            $total_commission = AgentAccount::where('user_id', $data['user_id'])
                ->whereIn('type', ['commission', 'claim'])
                ->when($fromDate, function ($q) use ($fromDate) {
                    $q->where('created_at', '>', $fromDate);
                })
                ->when($data['created_at'] ?? null, function ($q) use ($data) {
                    $q->where('created_at', '<=', $data['created_at']);
                })
                ->sum('amount');

            $total_sell = AgentAccount::where('user_id', $data['user_id'])
                ->where('type', 'sell')
                ->when($fromDate, function ($q) use ($fromDate) {
                    $q->where('created_at', '>', $fromDate);
                })
                ->when($data['created_at'] ?? null, function ($q) use ($data) {
                    $q->where('created_at', '<=', $data['created_at']);
                })
                ->sum('amount');

            $agent_payable = $total_sell + $old_due;

            $company_payable = $total_commission;

            $remaining_payable = $agent_payable - $company_payable;
            $current_due = $remaining_payable - $data['amount'];
        }
        AgentAccount::create([
            'user_id' => $data['user_id'],
            'type' => $data['type'],
            'amount' => $data['amount'],
            'old_due' => $current_due,
            'description' => $data['description'] ?? null,
            'payment_type' => $data['payment_type'] ?? null,
            'order_id' => $data['order_id'] ?? null,
            'created_by' => Auth::id(),
            'created_at' => $data['created_at'] ?? now(),
        ]);
    }
}
