<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

class AgentService
{
    protected $userService;

    function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    function createAgent($data): string
    {
        $user = $this->userService->createUser($data);

        Agent::updateOrCreate(
            ['user_id' => $user->id],
            [
                'username' => $data['username'],
                'trn' => $data['trn'] ?? null,
                'commission' => $data['commission'],
                'temp_password' => isset($data['plain_password'])
                    ? Crypt::encryptString($data['plain_password'])
                    : null,
            ]
        );

        return 'Agent created successfully';
    }

    public function updateUser(User $user, array $data): string
    {
        $this->userService->updateUser($user, $data);
        Agent::updateOrCreate(['user_id' => $user->id], [
            'username' => $data['username'],
            'trn' => $data['trn'],
            'commission' => $data['commission'],
        ]);
        return 'Agent updated successfully.';
    }

    public function deleteAgent(User $user): string
    {
        Agent::where('user_id', $user->id)->delete();
        $this->userService->delete($user);
        return 'Agent deleted successfully';
    }

    public function topTen($fromDate = null, $toDate = null)
    {
        if ($fromDate && $toDate) {
            $start = Carbon::parse($fromDate)->startOfDay(); // 00:00:00
            $end   = Carbon::parse($toDate)->endOfDay();     // 23:59:59
        } elseif ($fromDate) {
            $start = Carbon::parse($fromDate)->startOfDay();
            $end   = Carbon::parse($fromDate)->endOfDay();
        } elseif ($toDate) {
            $start = Carbon::parse($toDate)->startOfDay();
            $end   = Carbon::parse($toDate)->endOfDay();
        } else {
            // default current month
            $start = now()->startOfMonth();
            $end   = now()->endOfMonth();
        }

        return User::query()
            ->select(
                'users.id',
                'users.name',
                'users.photo',
                'users.status',
                'users.address'
            )
            ->leftJoin('orders', function ($join) use ($start, $end) {
                $join->on('orders.user_id', '=', 'users.id')
                    ->where('orders.status', 'Printed')
                    ->whereBetween('orders.created_at', [$start, $end]);
            })
            ->where('users.user_type', 'agent')
            ->selectRaw('COALESCE(SUM(orders.total_price),0) as total_sale')
            ->selectRaw('COUNT(orders.id) as orders_count')
            ->groupBy(
                'users.id',
                'users.name',
                'users.photo',
                'users.status',
                'users.address'
            )
            ->orderByDesc('total_sale')
            ->limit(10)
            ->get();
}
}
