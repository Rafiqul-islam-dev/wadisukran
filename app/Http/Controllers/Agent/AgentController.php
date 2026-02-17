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
    public function index(Request $request)
    {
        $users = User::where('user_type', 'agent')
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('agent', function ($q) use ($search) {
                            $q->where('username', 'like', "%{$search}%");
                        });
                });
            })
            ->whereHas('agent')
            ->with('agent:user_id,username,trn,commission')
            ->latest()
            ->get();

        return Inertia::render('Agent/Index', [
            'users' => $users,
        ]);
    }

    public function store(AgentRequest $request)
    {
        $validated = $request->validated();

        $name = $validated['name'];
        $firstWord = strtolower(strtok($name, ' '));
        do {
            $randomNumber = rand(100, 999);
            $username = $firstWord . '-' . $randomNumber;
        } while (Agent::where('username', $username)->exists());

        $validated['username'] = $username;
        $validated['password'] = $username;

        $this->agentService->createAgent($validated);

        return back()->with('success', 'Agent created successfully.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|unique:agents,username,' . $user->agent?->id,
            'commission' => 'required|numeric|min:0|max:100',
            'trn' => 'required|string|unique:agents,trn,' . $user->agent?->id,
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20|unique:users,phone,' . $user->id,
            'address' => 'nullable|string',
            'role' => 'required|string|exists:roles,name',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10048',
            'password' => 'nullable|string'
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
