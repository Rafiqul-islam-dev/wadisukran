<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\Incentive;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class IncentiveController extends Controller
{
    public function index(Request $request)
    {
        $agents = User::where('user_type', 'agent')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone']);

        $incentives = Incentive::query()
            ->with(['user:id,name,email,phone'])
            ->when($request->agent, function ($query, $agent) {
                $query->where('user_id', $agent);
            })
            ->when($request->from_date, function ($query, $fromDate) {
                $query->whereDate('incentive_date', '>=', Carbon::parse($fromDate)->toDateString());
            })
            ->when($request->to_date, function ($query, $toDate) {
                $query->whereDate('incentive_date', '<=', Carbon::parse($toDate)->toDateString());
            })
            ->latest('incentive_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $agent = $request->agent ? User::find($request->agent) : null;

        return Inertia::render('Accounts/Incentives', [
            'agents' => $agents,
            'incentives' => $incentives,
            'agent' => $agent,
            'filters' => [
                'agent' => $request->agent,
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'agent' => 'required|exists:users,id',
            'date' => 'required|date',
            'amount' => 'required|numeric|min:1',
            'incentive_type' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        Incentive::create([
            'user_id' => $data['agent'],
            'incentive_date' => Carbon::parse($data['date'])->toDateString(),
            'amount' => $data['amount'],
            'incentive_type' => $data['incentive_type'],
            'description' => $data['description'] ?? null,
            'created_by' => Auth::id(),
        ]);

        return back()->with('success', 'Incentive added successfully.');
    }

    public function update(Request $request, Incentive $incentive)
    {
        $data = $request->validate([
            'agent' => 'required|exists:users,id',
            'date' => 'required|date',
            'amount' => 'required|numeric|min:1',
            'incentive_type' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $incentive->update([
            'user_id' => $data['agent'],
            'incentive_date' => Carbon::parse($data['date'])->toDateString(),
            'amount' => $data['amount'],
            'incentive_type' => $data['incentive_type'],
            'description' => $data['description'] ?? null,
        ]);

        return back()->with('success', 'Incentive updated successfully.');
    }

    public function destroy(Incentive $incentive)
    {
        $incentive->delete();

        return back()->with('success', 'Incentive deleted successfully.');
    }
}
