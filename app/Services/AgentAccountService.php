<?php

namespace App\Services;

use App\Models\AgentAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AgentAccountService
{

    public function store(array $data)
    {
        if ($data['type'] === 'posting') {
            $previous_posting = AgentAccount::where('user_id', $data['user_id'])
                ->where('type', 'posting')
                ->where('created_at', '<', Carbon::parse($data['created_at'])->startOfDay())
                ->latest()
                ->first();

            $old_balance = 0;

            if (!$previous_posting) {
                $old_account = AgentAccount::where('user_id', $data['user_id'])
                    ->where('created_at', '<', Carbon::parse($data['created_at'])->startOfDay())
                    ->select('type', DB::raw('SUM(amount) as total_amount'))
                    ->groupBy('type')
                    ->get()
                    ->pluck('total_amount', 'type');

                $old_balance =
                    ($old_account['sell'] ?? 0)
                    - (
                        ($old_account['commission'] ?? 0)
                        + ($old_account['claim'] ?? 0)
                    );

            } else {
                $old_account = AgentAccount::where('user_id', $data['user_id'])
                    ->whereBetween('created_at', [
                        Carbon::parse($previous_posting->created_at)->endOfDay(),
                        Carbon::parse($data['created_at'])->startOfDay(),
                    ])
                    ->select('type', DB::raw('SUM(amount) as total_amount'))
                    ->groupBy('type')
                    ->get()
                    ->pluck('total_amount', 'type');

                $old_balance =
                    (($old_account['sell'] ?? 0) + $previous_posting->old_due)
                    - (
                        ($old_account['commission'] ?? 0)
                        + ($old_account['claim'] ?? 0)
                        + ($old_account['posting'] ?? 0)
                    );
            }
        }
        AgentAccount::create([
            'user_id' => $data['user_id'],
            'type' => $data['type'],
            'amount' => $data['amount'],
            'old_due' => $old_balance,
            'description' => $data['description'] ?? null,
            'payment_type' => $data['payment_type'] ?? null,
            'order_id' => $data['order_id'] ?? null,
            'created_by' => Auth::id(),
            'created_at' => $data['created_at'] ?? now(),
        ]);
    }
}
