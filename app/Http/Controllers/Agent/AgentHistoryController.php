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
            $users = User::where('id', $request->agent_id)
                ->where('user_type', 'agent')
                ->where('status', 'active')
                ->get();
        } else {
            $users = User::where('user_type', 'agent')
                ->where('status', 'active')
                ->get();
        }

        foreach ($users as $user) {
            $account = AgentAccount::where('user_id', $user->id)
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->select('type', DB::raw('SUM(amount) as total_amount'))
                ->groupBy('type')
                ->get()
                ->pluck('total_amount', 'type');

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

            $total_cancel = Order::where('user_id', $user->id)
                ->whereIn('status', ['Cancel', 'Cancel-Request'])
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->sum('total_price');

            $total_sell       = (float)($account['sell'] ?? 0);
            $total_commission = (float)($account['commission'] ?? 0);
            $total_win        = (float)($account['win'] ?? 0);
            $total_claim      = (float)($account['claim'] ?? 0);
            $total_posting    = (float)($account['posting'] ?? 0);
            $total_cancel     = (float)$total_cancel;

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

    return [
        'agents' => $agents_list,
        'agent_histories' => $agent_histories,
        'from_date' => $request->from ? Carbon::parse($request->from)->format('Y-m-d') : null,
        'to_date' => $request->to ? Carbon::parse($request->to)->format('Y-m-d') : null,
        'selected_agent_id' => $request->agent_id ?? null,
    ];
}

}
