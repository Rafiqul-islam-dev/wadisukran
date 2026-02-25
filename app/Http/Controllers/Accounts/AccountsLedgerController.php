<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\AgentAccount;
use App\Models\User;
use App\Services\AgentAccountService;
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
                ->with('user')
                ->latest()
                ->paginate(10);
        return Inertia::render('Accounts/Ledger', [
            'agents' => $agents,
            'ledgers' => $ledgers
        ]);
    }

    public function store(Request $request){
        $request->validate([
            'agent' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string',
            'payment_type' => 'required|string'
        ]);

        $data = [
            'user_id' => $request->agent,
            'type'    => 'posting',
            'amount'  => $request->amount,
            'description' => $request->description,
            'payment_type' => $request->payment_type
        ];

        $this->agentAccountService->store($data);
        return back();
    }
}
