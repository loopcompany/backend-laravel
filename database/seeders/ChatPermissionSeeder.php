<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ChatPermissionSeeder extends Seeder
{
    public function run()
    {
        // Chat resources permissions
        $chatPermissions = [
            // User Chat permissions 
        ];

        foreach ($chatPermissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'admin']
            );
            $this->command->info("✓ Permission created: {$permission}");
        }

        // Assign permissions to all admin roles
        $adminRoles = Role::where('guard_name', 'admin')->get();

        if ($adminRoles->count() > 0) {
            foreach ($adminRoles as $role) {
                $role->syncPermissions(Permission::where('guard_name', 'admin')->get());
                $this->command->info("✓ Permissions assigned to role: {$role->name}");
            }
        } else {
            $this->command->warn("\n⚠ No admin roles found");
        }

        $this->command->info("\n✅ Chat permissions setup completed!");
    }
}
