<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Services\UserService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    protected $userService;
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
    public function index()
    {
        $users = User::where('user_type', 'admin')->with('roles:id,name')->get();

        $roles = Role::orderBy('name')
            ->whereNotIn('name', ['agent', 'Super Admin'])
            ->with(['permissions'])
            ->get();


        return Inertia::render('User/Index', [
            'users' => $users,
            'roles' => $roles
        ]);
    }

    public function store(UserRequest $request)
    {
        $validated = $request->validated();
        $this->userService->createUser($validated);
        return redirect()->back()->with('success', 'User created successfully!');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20|unique:users,phone,' . $user->id,
            'address' => 'nullable|string',
            'role' => 'required|string|exists:roles,name',
            'password' => 'nullable|string|min:8|confirmed',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10048'
        ]);

        $this->userService->updateUser($user, $validated);

        return redirect()->back()->with('success', 'User updated successfully!');
    }

    public function updateStatus(User $user)
    {
        $this->userService->statusChange($user);

        return redirect()->back()->with('success', 'User status updated successfully!');
    }

    public function delete(User $user)
    {
        $this->userService->delete($user);

        return back()->with('User deleted successfully');
    }
}
