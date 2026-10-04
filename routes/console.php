<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// اجرای درخواست‌های حذف حساب بعد از مهلت ۱۴ روزه (نیاز به cron: php artisan schedule:run)
Schedule::command('accounts:process-deletions')->hourly()->withoutOverlapping();
