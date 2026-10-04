<?php

namespace App\Console\Commands;

use App\Services\Security\AccountDeletionService;
use Illuminate\Console\Command;

class ProcessAccountDeletions extends Command
{
    protected $signature = 'accounts:process-deletions';

    protected $description = 'اجرای درخواست‌های حذف حساب که مهلت انصرافشان تمام شده است';

    public function handle(AccountDeletionService $deletions): int
    {
        $count = $deletions->processDue();
        $this->info("{$count} درخواست حذف حساب اجرا شد.");

        return self::SUCCESS;
    }
}
