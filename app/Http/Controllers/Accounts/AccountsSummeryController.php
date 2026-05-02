<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\AgentAccount;
use App\Models\AgentBill;
use App\Models\Incentive;
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

class AccountsSummeryController extends Controller
{
    protected $agentAccountService;

    public function __construct(AgentAccountService $agentAccountService)
    {
        $this->agentAccountService = $agentAccountService;
    }

    public function index(Request $request)
    {
        $agents_list = User::where('user_type', 'agent')
            ->where('status', 'active')
            ->get();

        $agent = null;

        if ($request->from && $request->to && $request->agent_id) {
            $fromDate = Carbon::parse($request->from)->toDateString();
            $toDate = Carbon::parse($request->to)->toDateString();
            $fromStart = Carbon::parse($fromDate)->startOfDay();
            $toEnd = Carbon::parse($toDate)->endOfDay();

            $account = AgentAccount::where('user_id', $request->agent_id)
                ->whereBetween('created_at', [$fromStart, $toEnd])
                ->select('type', DB::raw('SUM(amount) as total_amount'))
                ->groupBy('type')
                ->get()
                ->pluck('total_amount', 'type');

            $previous_posting = AgentAccount::where('user_id', $request->agent_id)
                ->where('type', 'posting')
                ->where('created_at', '<', $fromStart)
                ->latest()
                ->first();

            $old_balance = 0;

            if (!$previous_posting) {
                $old_account = AgentAccount::where('user_id', $request->agent_id)
                    ->where('created_at', '<', $fromStart)
                    ->select('type', DB::raw('SUM(amount) as total_amount'))
                    ->groupBy('type')
                    ->get()
                    ->pluck('total_amount', 'type');

                $old_incentive = Incentive::where('user_id', $request->agent_id)
                    ->whereDate('incentive_date', '<', $fromDate)
                    ->sum('amount');

                $old_balance =
                    ($old_account['sell'] ?? 0)
                    - (
                        ($old_account['commission'] ?? 0)
                        + ($old_account['claim'] ?? 0)
                        + $old_incentive
                    );
            } else {
                $old_account = AgentAccount::where('user_id', $request->agent_id)
                    ->where('created_at', '>=', $previous_posting->created_at)
                    ->where('created_at', '<', $fromStart)
                    ->select('type', DB::raw('SUM(amount) as total_amount'))
                    ->groupBy('type')
                    ->get()
                    ->pluck('total_amount', 'type');

                $old_incentive = Incentive::where('user_id', $request->agent_id)
                    ->whereDate('incentive_date', '>=', $previous_posting->created_at->toDateString())
                    ->whereDate('incentive_date', '<', $fromDate)
                    ->sum('amount');

                $old_balance =
                    (($old_account['sell'] ?? 0) + $previous_posting->old_due)
                    - (
                        ($old_account['commission'] ?? 0)
                        + ($old_account['claim'] ?? 0)
                        + $old_incentive
                    );
            }

            $total_cancel = Order::where('user_id', $request->agent_id)
                ->whereIn('status', ['Cancel', 'Cancel-Request'])
                ->whereBetween('created_at', [$fromStart, $toEnd])
                ->sum('total_price');

            // Only incentives added inside selected summary date range will be shown here.
            $total_incentive = Incentive::where('user_id', $request->agent_id)
                ->whereDate('incentive_date', '>=', $fromDate)
                ->whereDate('incentive_date', '<=', $toDate)
                ->sum('amount');

            if ($account) {
                $userDetails = User::find($request->agent_id);

                $agent = [
                    'agent_name'       => $userDetails->name,
                    'agent_address'    => $userDetails->address,
                    'total_sell'       => !empty($account['sell']) ? $account['sell'] : 0,
                    'total_commission' => !empty($account['commission']) ? $account['commission'] : 0,
                    'total_win'        => !empty($account['win']) ? $account['win'] : 0,
                    'total_claim'      => !empty($account['claim']) ? $account['claim'] : 0,
                    'total_posting'    => !empty($account['posting']) ? $account['posting'] : 0,
                    'total_cancel'     => $total_cancel,
                    'total_incentive'  => $total_incentive,
                    'net_amount'       =>
                        ($account['sell'] ?? 0)
                        - (
                            ($account['commission'] ?? 0)
                            + ($account['claim'] ?? 0)
                            + ($account['posting'] ?? 0)
                            + $total_incentive
                        ),
                    'old_balance'      => $old_balance,
                    'total_due'        => (($account['sell'] ?? 0) + $old_balance)
                        - (
                            ($account['commission'] ?? 0)
                            + ($account['claim'] ?? 0)
                            + ($account['posting'] ?? 0)
                            + $total_incentive
                        ),
                ];
            }
        }

        return Inertia::render('Accounts/Summery', [
            'agents' => $agents_list,
            'agent' => $agent,
            'from_date' => $request->from ? Carbon::parse($request->from)->format('Y-m-d') : null,
            'to_date' => $request->to ? Carbon::parse($request->to)->format('Y-m-d') : null,
        ]);
    }
}
