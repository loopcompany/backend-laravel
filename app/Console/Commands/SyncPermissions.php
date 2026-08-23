<?php

namespace App\Console\Commands;

use App\Services\AdminRoleService;
use Illuminate\Console\Command;

class SyncPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'همگام‌سازی permissions و roles با AdminRoleService';

    /**
     * Execute the console command.
     */
    public function handle(AdminRoleService $service)
    {
        $this->info('🔄 شروع همگام‌سازی permissions و roles...');
        $this->newLine();

        // 1. ایجاد/بروزرسانی permissions
        $this->info('📝 در حال ایجاد permissions...');
        $permissionsResult = $service->createBasicPermissions();
        
        if ($permissionsResult['success']) {
            $this->info('✅ ' . $permissionsResult['message']);
            $this->info('   تعداد: ' . count($permissionsResult['permissions']));
        } else {
            $this->error('❌ خطا در ایجاد permissions: ' . $permissionsResult['error']);
            return 1;
        }

        $this->newLine();

        // 2. ایجاد/بروزرسانی roles
        $this->info('👥 در حال ایجاد/بروزرسانی roles...');
        $rolesResult = $service->createBasicRoles();
        
        if ($rolesResult['success']) {
            $this->info('✅ ' . $rolesResult['message']);
            $this->info('   سمت‌ها: ' . implode(', ', $rolesResult['roles']));
        } else {
            $this->error('❌ خطا در ایجاد roles: ' . $rolesResult['error']);
            return 1;
        }

        $this->newLine();

        // 3. نمایش خلاصه
        $this->info('📊 خلاصه:');
        $this->table(
            ['نقش', 'تعداد Permissions'],
            [
                ['super-admin', 'همه (' . count($permissionsResult['permissions']) . ')'],
                ['admin', 'محدود'],
                ['moderator', 'محدود'],
                ['editor', 'محدود'],
                ['viewer', 'محدود'],
            ]
        );

        $this->newLine();
        $this->info('✨ همگام‌سازی با موفقیت انجام شد!');
        $this->info('💡 حالا می‌توانید به پنل ادمین وارد شوید و WarrantyResource را مشاهده کنید.');

        return 0;
    }
}
