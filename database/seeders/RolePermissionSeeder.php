<?php

namespace Database\Seeders;

use App\Services\AdminRoleService;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function __construct(
        protected AdminRoleService $adminRoleService
    ) {}

    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $this->command->info('🚀 شروع راه‌اندازی سیستم نقش و مجوز...');

        // استفاده از Service برای راه‌اندازی کامل سیستم
        $result = $this->adminRoleService->setupCompleteRoleSystem();

        if ($result['success']) {
            $this->command->info('✅ ' . $result['message']);
            
            // نمایش اطلاعات اعتبارسنجی
            $credentials = $result['data']['super_admin_credentials'];
            $this->command->info('📧 ایمیل سوپر ادمین: ' . $credentials['email']);
            $this->command->info('🔑 رمز عبور: ' . $credentials['password']);
            
            // نمایش آمار
            $this->command->info('📊 آمار ایجاد شده:');
            $this->command->info('   - Permissions: ' . count($result['data']['permissions']));
            $this->command->info('   - Roles: ' . count($result['data']['roles']));
            
        } else {
            $this->command->error('❌ ' . $result['message']);
            if (isset($result['error'])) {
                $this->command->error('خطا: ' . $result['error']);
            }
        }
    }
}
