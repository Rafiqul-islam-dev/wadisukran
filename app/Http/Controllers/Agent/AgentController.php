<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\AgentRequest;
use App\Models\Agent;
use App\Models\User;
use App\Services\AgentService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AgentController extends Controller
{
    protected $agentService;
    function __construct(AgentService $agentService)
    {
        $this->agentService = $agentService;
    }
    public function index()
    {
        $users = User::where('user_type', 'agent')->whereHas('agent')->with('agent:user_id,username,trn')->latest()->get();

        return Inertia::render('Agent/Index', [
            'users' => $users,
        ]);
    }

    public function store(AgentRequest $request)
    {
        $validated = $request->validated();

        $this->agentService->createAgent($validated);

        return back()->with('success', 'Agent created successfully.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|unique:agents,username,' . $user->agent?->id,
            'trn' => 'required|string|unique:agents,trn,' . $user->agent?->id,
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20|unique:users,phone,' . $user->id,
            'address' => 'nullable|string',
            'role' => 'required|string|exists:roles,name',
            'password' => 'nullable|string|min:8|confirmed',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10048'
        ]);

        $this->agentService->updateUser($user, $validated);

        return back()->with('success', 'Agent updated successfully.');
    }

    public function delete(User $user)
    {
        $this->agentService->deleteAgent($user);

        return back()->with('Agent deleted successfully');
    }

    public function trashed_agents()
    {
        $users = User::onlyTrashed()
            ->where('user_type', 'agent')
            ->whereHas('agent', function ($q) {
                $q->withTrashed();
            })
            ->with([
                'agent' => function ($q) {
                    $q->withTrashed()->select('user_id', 'username', 'trn', 'deleted_at');
                }
            ])
            ->latest('deleted_at')
            ->get();

        return Inertia::render('Agent/Trashed', [
            'users' => $users,
        ]);
    }

    public function restore_agent($user)
    {
        try {
            DB::transaction(function () use ($user) {
                $user = User::onlyTrashed()->findOrFail($user);
                $user->restore();

                Agent::withTrashed()
                    ->where('user_id', $user->id)
                    ->restore();
            });

            return back()->with('success', 'Agent restored successfully.');
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function permanent_delete_agent($user)
    {
        try {
            $user = User::onlyTrashed()->findOrFail($user);
            if ($user->photo) {
                if (file_exists(public_path($user->photo))) {
                    unlink(public_path($user->photo));
                }
            }
            Agent::withTrashed()->where('user_id', $user->id)->forceDelete();
            $user->forceDelete();

            return back()->with('success', 'User deleted successfully.');
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
