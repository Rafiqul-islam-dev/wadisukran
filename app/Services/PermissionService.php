<?php

namespace App\Services;

use Spatie\Permission\Models\Permission;
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


}
