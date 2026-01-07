<?php

namespace App\Services;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionService
{
    public function createPermission($data): string
    {
        Permission::create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return 'Permission created successfully';
    }

    public function updatePermission($permissionId, array $data): string
    {
        $permission = Permission::findOrFail($permissionId);

        $permission->update([
            'name' => $data['name']
        ]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return 'Permission updated successfully';
    }

    public function deletePermission($permissionId): string
    {
        $permission = Permission::findOrFail($permissionId);
        $permission->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return 'Permission deleted successfully';
    }

    public function createRole(array $data): string
    {
        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        if (!empty($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return 'Role created successfully';
    }


    public function updateRole(int $roleId, array $data): string
    {
        $role = Role::findOrFail($roleId);

        $role->update([
            'name' => $data['name'],
        ]);

        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return 'Role updated successfully';
    }


    public function deleteRole(int $roleId): string
    {
        $role = Role::findOrFail($roleId);

        $role->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return 'Role deleted successfully';
    }
}
