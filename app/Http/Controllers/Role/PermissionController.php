<?php

namespace App\Http\Controllers\Role;

use App\Http\Controllers\Controller;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function index()
    {
        // return Permission::pluck('name')->toArray();
        $permissions = Permission::withCount('roles')
            ->orderByDesc('updated_at')
            ->paginate(10);

        return Inertia::render('Permission/Index', [
            'permissions' => $permissions
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
        ]);

        $this->permissionService->createPermission($data);

        return back()->with('Permission Created.');
    }

    public function update(Request $request, Permission $permission)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $permission->id,
        ]);

        $this->permissionService->updatePermission($permission->id, $data);

        return back()->with('Permission updated successfully');
    }
    public function delete(Permission $permission)
    {
        $this->permissionService->deletePermission($permission->id);

        return back()->with('Permission deleted successfully');
    }
}
