<?php

namespace Tests\Feature;

use App\Models\Admin;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminRoleAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_be_assigned_roles()
    {
        // Create roles
        $superAdminRole = Role::create(['name' => 'super-admin', 'guard_name' => 'admin']);
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'admin']);
        
        // Create admin
        $admin = Admin::create([
            'name' => 'Test Admin',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        
        // Assign role using ID (this should work)
        $admin->assignRole($adminRole->id);
        
        // Test role assignment
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertEquals(1, $admin->roles->count());
        $this->assertEquals('admin', $admin->roles->first()->name);
        
        echo "✅ Role assignment test passed!\n";
        echo "Admin: {$admin->name} has role: {$admin->roles->first()->name}\n";
    }
}