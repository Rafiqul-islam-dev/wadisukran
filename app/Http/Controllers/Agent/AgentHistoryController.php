<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\AgentAccount;
use App\Models\AgentBill;
use App\Models\Order;
use App\Models\User;
use App\Services\AgentAccountService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use ZipArchive;


class AgentHistoryController extends Controller
{
protected $agentAccountService;

    public function __construct(AgentAccountService $agentAccountService)
    {
        $this->agentAccountService = $agentAccountService;
    }

    public function index(Request $request)
    {
        $data = $this->getAgentHistoryData($request);

        return Inertia::render('Agent/AgentHistory/Index', $data);
    }
    public function pdf(Request $request)
    {
        $data = $this->getAgentHistoryData($request);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.agent-history-report', [
            'agent_histories' => $data['agent_histories'],
            'from_date' => $data['from_date'],
            'to_date' => $data['to_date'],
            'selected_agent_id' => $data['selected_agent_id'],
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('agent-history-report.pdf');
    }

    private function getAgentHistoryData(Request $request)
    {
        $agents_list = User::where('user_type', 'agent')
            ->where('status', 'active')
            ->get();

        $agent_histories = [];

        if ($request->from && $request->to) {
            $fromDate = Carbon::parse($request->from)->startOfDay();
            $toDate   = Carbon::parse($request->to)->endOfDay();

            if ($request->agent_id) {
                // DAILY MODE for single agent
                $user = User::where('id', $request->agent_id)
                    ->where('user_type', 'agent')
                    ->where('status', 'active')
                    ->first();

                if ($user) {
                    $currentDate = $fromDate->copy();
                    $running_balance = $this->calculateOldBalance($user, $fromDate);

                    while ($currentDate <= $toDate) {
                        $dayStart = $currentDate->copy()->startOfDay();
                        $dayEnd = $currentDate->copy()->endOfDay();

                        $stats = $this->getAgentStatsForPeriod($user, $dayStart, $dayEnd);
                        
                        $daily_sell = $stats['sell'];
                        $daily_commission = $stats['commission'];
                        $daily_claim = $stats['claim'];
                        $daily_posting = $stats['posting'];
                        $daily_cancel = $stats['cancel'];
                        $daily_win = $stats['win'];

                        $net_amount = $daily_sell - ($daily_commission + $daily_claim + $daily_posting);
                        $total_due = ($daily_sell + $running_balance) - ($daily_commission + $daily_claim + $daily_posting);

                        $firstCreatedAt = $stats['first_created_at'] ? Carbon::parse($stats['first_created_at']) : null;
                        $lastCreatedAt  = $stats['last_created_at'] ? Carbon::parse($stats['last_created_at']) : null;

                        $firstCreatedAt = $stats['first_created_at'] ? Carbon::parse($stats['first_created_at']) : null;
                        $lastCreatedAt  = $stats['last_created_at'] ? Carbon::parse($stats['last_created_at']) : null;

                        if ($firstCreatedAt && $lastCreatedAt) {
                            $dateTime = $firstCreatedAt->format('Y-m-d')
                                . ' ('
                                . $firstCreatedAt->format('g:i:s A')
                                . ' - '
                                . $lastCreatedAt->format('g:i:s A')
                                . ')';
                        } else {
                            $dateTime = $dayStart->format('Y-m-d');
                        }

                        $agent_histories[] = [
                            'date'             => $dayStart->format('Y-m-d'),
                            'date_time'        => $dateTime,
                            'agent_id'         => $user->id,
                            'agent_name'       => $user->name,
                            'agent_address'    => $user->address,
                            'total_sell'       => round($daily_sell, 2),
                            'total_commission' => round($daily_commission, 2),
                            'total_win'        => round($daily_win, 2),
                            'total_claim'      => round($daily_claim, 2),
                            'total_posting'    => round($daily_posting, 2),
                            'total_cancel'     => round($daily_cancel, 2),
                            'old_balance'      => round($running_balance, 2),
                            'net_amount'       => round($net_amount, 2),
                            'total_due'        => round($total_due, 2),
                        ];

                        $running_balance = $total_due;
                        $currentDate->addDay();
                    }
                }
            } else {
                // SUMMARY MODE for all agents
                $users = User::where('user_type', 'agent')
                    ->where('status', 'active')
                    ->get();

                foreach ($users as $user) {
                    $old_balance = $this->calculateOldBalance($user, $fromDate);
                    $stats = $this->getAgentStatsForPeriod($user, $fromDate, $toDate);

                    $total_sell       = $stats['sell'];
                    $total_commission = $stats['commission'];
                    $total_win        = $stats['win'];
                    $total_claim      = $stats['claim'];
                    $total_posting    = $stats['posting'];
                    $total_cancel     = $stats['cancel'];

                    if (
                        $total_sell == 0 &&
                        $total_commission == 0 &&
                        $total_win == 0 &&
                        $total_claim == 0 &&
                        $total_posting == 0 &&
                        $total_cancel == 0 &&
                        $old_balance == 0
                    ) {
                        continue;
                    }

                    $net_amount = $total_sell - ($total_commission + $total_claim + $total_posting);
                    $total_due  = ($total_sell + $old_balance) - ($total_commission + $total_claim + $total_posting);

                    $agent_histories[] = [
                        'agent_id'         => $user->id,
                        'agent_name'       => $user->name,
                        'agent_address'    => $user->address,
                        'total_sell'       => round($total_sell, 2),
                        'total_commission' => round($total_commission, 2),
                        'total_win'        => round($total_win, 2),
                        'total_claim'      => round($total_claim, 2),
                        'total_posting'    => round($total_posting, 2),
                        'total_cancel'     => round($total_cancel, 2),
                        'old_balance'      => round($old_balance, 2),
                        'net_amount'       => round($net_amount, 2),
                        'total_due'        => round($total_due, 2),
                    ];
                }
            }
        }

        return [
            'agents' => $agents_list,
            'agent_histories' => $agent_histories,
            'from_date' => $request->from ? Carbon::parse($request->from)->format('Y-m-d') : null,
            'to_date' => $request->to ? Carbon::parse($request->to)->format('Y-m-d') : null,
            'selected_agent_id' => $request->agent_id ?? null,
        ];
    }

    private function calculateOldBalance($user, $fromDate)
    {
        $previous_posting = AgentAccount::where('user_id', $user->id)
            ->where('type', 'posting')
            ->where('created_at', '<', $fromDate)
            ->latest()
            ->first();

        $old_balance = 0;

        if (!$previous_posting) {
            $old_account = AgentAccount::where('user_id', $user->id)
                ->where('created_at', '<', $fromDate)
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
            $old_account = AgentAccount::where('user_id', $user->id)
                ->whereBetween('created_at', [
                    $previous_posting->created_at,
                    $fromDate
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
                );
        }

        return $old_balance;
    }

    private function getAgentStatsForPeriod($user, $start, $end)
    {
        $account = AgentAccount::where('user_id', $user->id)
            ->whereBetween('created_at', [$start, $end])
            ->select('type', DB::raw('SUM(amount) as total_amount'))
            ->groupBy('type')
            ->get()
            ->pluck('total_amount', 'type');

        // ওই date range এর actual first and last created_at time
        $timeRow = AgentAccount::where('user_id', $user->id)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('MIN(created_at) as first_created_at, MAX(created_at) as last_created_at')
            ->first();

        $total_cancel = Order::where('user_id', $user->id)
            ->whereIn('status', ['Cancel', 'Cancel-Request'])
            ->whereBetween('created_at', [$start, $end])
            ->sum('total_price');

        return [
            'sell'             => (float)($account['sell'] ?? 0),
            'commission'       => (float)($account['commission'] ?? 0),
            'win'              => (float)($account['win'] ?? 0),
            'claim'            => (float)($account['claim'] ?? 0),
            'posting'          => (float)($account['posting'] ?? 0),
            'cancel'           => (float)$total_cancel,
            'first_created_at' => $timeRow?->first_created_at,
            'last_created_at'  => $timeRow?->last_created_at,
        ];
    }

}
