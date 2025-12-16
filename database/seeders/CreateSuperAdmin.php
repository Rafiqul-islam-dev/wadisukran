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
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $required_permissions = [
            "agent create",
            "agent delete",
            "agent update",
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
            "show company settings",
            "show daily summery report",
            "show dashboard",
            "show order history",
            "show permissions",
            "show product list",
            "show report",
            "show roles",
            "show settings",
            "show user list",
            "show users",
            "user create",
            "user delete",
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
