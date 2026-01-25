<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreateSuperAdmin extends Seeder
{
    public function run(): void
    {
        $required_permissions = [
            "agent create",
            "agent delete",
            "agent permanent delete",
            "agent restore",
            "agent update",
            "draw history delete",
            "permission create",
            "permission delete",
            "permission update",
            "role create",
            "role delete",
            "role permission",
            "role update",
            "show agent list",
            "show banner list",
            "show cancel order",
            "show categories",
            "show check winners",
            "show company settings",
            "show daily summery report",
            "show dashboard",
            "show draw history",
            "show draws",
            "show order history",
            "show permissions",
            "show probable wins",
            "show product list",
            "show report",
            "show roles",
            "show settings",
            "show today commission",
            "show today sales",
            "show total agents",
            "show trashed agents",
            "show trashed products",
            "show trashed users",
            "show user list",
            "show users",
            "show winner report",
            "user create",
            "user delete",
            "user permanent delete",
            "user restore",
            "user status change",
            "user update"
        ];

        foreach ($required_permissions as $permission) {
            Permission::updateOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }

        $superAdminRole = Role::firstOrCreate(
            ['name' => 'Super Admin', 'guard_name' => 'web']
        );
        $permissions = Permission::pluck('name')->toArray();
        $superAdminRole->syncPermissions($permissions);

        $user = User::firstOrCreate(
            ['email' => 'superadmin@gmail.com'],
            [
                'user_type' => 'admin',
                'name' => 'Super Admin',
                'password' => Hash::make('12345678'),
            ]
        );
        if (! $user->hasRole('Super Admin')) {
            $user->syncRoles(['Super Admin']);
        }

        $this->command->info('✅ Super Admin created and all permissions assigned.');
    }
}
