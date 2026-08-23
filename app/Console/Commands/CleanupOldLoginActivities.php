<?php

namespace App\Console\Commands;

use App\Services\LoginActivityService;
use Illuminate\Console\Command;

class CleanupOldLoginActivities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'login-activities:cleanup {--days=90 : Number of days to keep}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'حذف رکوردهای قدیمی فعالیت‌های ورود/خروج';

    /**
     * Execute the console command.
     */
    public function handle(LoginActivityService $service): int
    {
        $days = (int) $this->option('days');

        $this->info("در حال حذف فعالیت‌های قدیمی‌تر از {$days} روز...");

        $result = $service->cleanupOldActivities($days);

        if ($result['success']) {
            $this->info($result['message']);
            $this->info("تعداد حذف شده: {$result['deleted_count']}");
            return Command::SUCCESS;
        }

        $this->error($result['message']);
        return Command::FAILURE;
    }
}
