<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\AgentAccount;
use App\Models\Incentive;
use App\Models\User;
use App\Services\AgentAccountService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AccountsLedgerController extends Controller
{
    protected $agentAccountService;
    public function __construct(AgentAccountService $agentAccountService)
    {
        $this->agentAccountService = $agentAccountService;
    }
    public function index(Request $request){
        $agents = User::where('user_type', 'agent')->where('status', 'active')->get();
        $ledgers = AgentAccount::where('type', 'posting')
                ->when($request->agent, function($q, $agent){
                    $q->where('user_id', $agent);
                })
                ->when($request->from_date && $request->to_date, function ($q) use ($request) {
                    $from = Carbon::parse($request->from_date)->startOfDay();
                    $to   = Carbon::parse($request->to_date)->endOfDay();

                    $q->whereBetween('created_at', [$from, $to]);
                })
                ->where('amount', '!=', 0)
                ->with('user')
                ->latest()
                ->paginate(10);

        $agent = null;
        if($request->agent){
            $agent = User::find($request->agent);
        }
        return Inertia::render('Accounts/Ledger', [
            'agents' => $agents,
            'ledgers' => $ledgers,
            'agent' => $agent
        ]);
    }

    public function store(Request $request){
        $request->validate([
            'agent' => 'required|exists:users,id',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'payment_type' => 'required|numeric|in:1,2',
            'amount' => [
                'required',
                'numeric',
                'min:1',
                function ($attribute, $value, $fail) use ($request) {
                    $account = AgentAccount::where('user_id', $request->agent)
                        ->selectRaw('type, sum(amount) as amount')
                        ->groupBy('type')
                        ->pluck('amount', 'type');

                    $total_incentive = Incentive::where('user_id', $request->agent)->sum('amount');

                    $total_due = ($account['sell'] ?? 0) 
                               - (
                                   ($account['commission'] ?? 0) 
                                   + ($account['claim'] ?? 0) 
                                   + ($account['posting'] ?? 0)
                                   + $total_incentive
                               );

                    if ($request->payment_type == 1) {
                        if ($value > $total_due) {
                            $fail('Amount cannot be greater than total due (' . number_format($total_due, 2) . ').');
                        }
                    } else {
                        if ($total_due >= 0) {
                            $fail('Cannot process payment. Total due is positive or zero (' . number_format($total_due, 2) . ').');
                        } elseif ($value > abs($total_due)) {
                            $fail('Amount cannot be greater than (' . number_format(abs($total_due), 2) . ').');
                        }
                    }
                },
            ],
        ]);

        $data = [
            'user_id' => $request->agent,
            'type'    => 'posting',
            'created_at' => Carbon::parse($request->date)->endOfDay(),
            'amount'  => $request->payment_type == 1 ? $request->amount : -$request->amount,
            'description' => $request->description,
            'payment_type' => $request->payment_type
        ];

        $this->agentAccountService->store($data);
        return back();
    }

    public function update(Request $request, AgentAccount $ledger){
        $request->validate([
            'agent' => 'required|exists:users,id',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'payment_type' => 'required|numeric|in:1,2',
            'amount' => [
                'required',
                'numeric',
                'min:1',
                function ($attribute, $value, $fail) use ($request, $ledger) {
                    $account = AgentAccount::where('user_id', $request->agent)
                        ->where('id', '!=', $ledger->id) // Exclude current ledger for update calculation
                        ->selectRaw('type, sum(amount) as amount')
                        ->groupBy('type')
                        ->pluck('amount', 'type');

                    $total_incentive = Incentive::where('user_id', $request->agent)->sum('amount');

                    $total_due = ($account['sell'] ?? 0) 
                               - (
                                   ($account['commission'] ?? 0) 
                                   + ($account['claim'] ?? 0) 
                                   + ($account['posting'] ?? 0)
                                   + $total_incentive
                               );

                    if ($request->payment_type == 1) {
                        if ($value > $total_due) {
                            $fail('Amount cannot be greater than total due (' . number_format($total_due, 2) . ').');
                        }
                    } else {
                        if ($total_due >= 0) {
                            $fail('Cannot process payment. Total due is positive or zero (' . number_format($total_due, 2) . ').');
                        } elseif ($value > abs($total_due)) {
                            $fail('Amount cannot be greater than the available negative balance (' . number_format(abs($total_due), 2) . ').');
                        }
                    }
                },
            ],
        ]);

        $data = [
            'user_id' => $request->agent,
            'created_at' => Carbon::parse($request->date)->endOfDay(),
            'amount'  => $request->payment_type == 1 ? $request->amount : -$request->amount,
            'description' => $request->description,
            'payment_type' => $request->payment_type
        ];

        $ledger->update($data);
        return back();
    }

    public function pdf(Request $request){
        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date',
        ]);

        $agents = User::where('user_type', 'agent')->where('status', 'active')->get();
        $ledgers = AgentAccount::where('type', 'posting')
                ->when($request->agent, function($q, $agent){
                    $q->where('user_id', $agent);
                })
                ->when($request->from_date && $request->to_date, function ($q) use ($request) {
                    $from = Carbon::parse($request->from_date)->startOfDay();
                    $to   = Carbon::parse($request->to_date)->endOfDay();

                    $q->whereBetween('created_at', [$from, $to]);
                })
                ->where('amount', '!=', 0)
                ->with('user')
                ->latest()
                ->get();

        $agent = null;
        if($request->agent){
            $agent = User::find($request->agent);
        }

        $pdf = Pdf::loadView('pdf.ledger', [
            'ledgers' => $ledgers,
            'agent' => $agent,
            'from_date' => $request->from_date,
            'to_date' => $request->to_date,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('ledger-report.pdf');
    }
}
