<?php

namespace App\Http\Controllers\Role;

use App\Http\Controllers\Controller;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }
    public function index()
    {
        $roles = Role::withCount(['users', 'permissions'])
            ->with('permissions')
            ->orderBy('name')
            ->get();


        $permissions = Permission::withCount('roles')
            ->orderByDesc('updated_at')
            ->get();

        return Inertia::render('Role/Index', [
            'roles' => $roles,
            'permissions' => $permissions
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name'
        ]);

        $this->permissionService->createRole($validated);

        return redirect()->back()->with('success', 'Role created successfully!');
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name'
        ]);

        $this->permissionService->updateRole($role->id, $validated);

        return redirect()->back()->with('success', 'Role updated successfully!');
    }

    public function delete(Role $role)
    {
        $this->permissionService->deleteRole($role->id);

        return redirect()->back()->with('success', 'Role deleted successfully!');
    }
}
