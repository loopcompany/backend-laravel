<?php

namespace App\Console\Commands;

use App\Services\AdminRoleService;
use Illuminate\Console\Command;

class SetupBlogPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup all system permissions and update roles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Setting up system permissions...');
        
        $service = new AdminRoleService();
        
        // Create permissions
        $this->line('Creating basic permissions...');
        $permissionResult = $service->createBasicPermissions();
        
        if ($permissionResult['success']) {
            $this->info('✓ Permissions created successfully');
            $this->line('Created permissions: ' . implode(', ', $permissionResult['permissions']));
        } else {
            $this->error('✗ Failed to create permissions: ' . $permissionResult['message']);
            return 1;
        }
        
        // Create/update roles
        $this->line('Creating/updating roles...');
        $roleResult = $service->createBasicRoles();
        
        if ($roleResult['success']) {
            $this->info('✓ Roles updated successfully');
            $this->line('Updated roles: ' . implode(', ', $roleResult['roles']));
        } else {
            $this->error('✗ Failed to update roles: ' . $roleResult['message']);
            return 1;
        }
        
        $this->newLine();
        $this->info('🎉 Permissions setup completed successfully!');
        $this->line('Available blog permissions:');
        $this->line('- view-blogs: مشاهده مقالات');
        $this->line('- create-blogs: ایجاد مقاله جدید');
        $this->line('- edit-blogs: ویرایش مقالات');
        $this->line('- delete-blogs: حذف مقالات');
        $this->newLine();
        $this->line('Available technician permissions:');
        $this->line('- view-technicians: مشاهده تکنسین‌ها');
        $this->line('- create-technicians: ایجاد تکنسین');
        $this->line('- edit-technicians: ویرایش تکنسین‌ها');
        $this->line('- delete-technicians: حذف تکنسین‌ها');
        
        return 0;
    }
}
