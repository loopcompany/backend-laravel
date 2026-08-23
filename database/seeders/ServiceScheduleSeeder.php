<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ServiceSchedule;
use Illuminate\Support\Facades\DB;

class ServiceScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('service_schedules')->truncate();

        $schedules = [
            // گزینه‌های اصلی (کوتاه مدت / بلند مدت)
            [
                'type' => 'main',
                'term_type' => 'short_term',
                'label' => 'کوتاه مدت',
                'value' => 'short_term',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'type' => 'main',
                'term_type' => 'long_term',
                'label' => 'بلند مدت',
                'value' => 'long_term',
                'sort_order' => 2,
                'is_active' => true,
            ],

            // گزینه‌های مدت زمان برای بلند مدت
            [
                'type' => 'duration',
                'term_type' => 'long_term',
                'label' => '60 روزه هر 15 روز کاری',
                'value' => '60_days_every_15',
                'sort_order' => 1,
                'is_active' => true,
                
            ],
            [
                'type' => 'duration',
                'term_type' => 'long_term',
                'label' => '120 روزه هر 15 روز کاری',
                'value' => '120_days_every_15',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'type' => 'duration',
                'term_type' => 'long_term',
                'label' => '120 روزه هر 30 روز کاری',
                'value' => '120_days_every_30',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'type' => 'duration',
                'term_type' => 'long_term',
                'label' => 'یکسال هر یک ماه',
                'value' => '1_year_every_month',
                'sort_order' => 4,
                'is_active' => true,
            ],

            // گزینه‌های ساعت برای بلند مدت
            [
                'type' => 'time',
                'term_type' => 'long_term',
                'label' => '10 صبح به بعد',
                'value' => '10_am_onwards',
                'sort_order' => 1,
                'is_active' => true,
                'start_time' => '10:00:00'
            ],
            [
                'type' => 'time',
                'term_type' => 'long_term',
                'label' => '12 ظهر به بعد',
                'value' => '12_pm_onwards',
                'sort_order' => 2,
                'is_active' => true,
                'start_time' => '12:00:00'
            ],
            [
                'type' => 'time',
                'term_type' => 'long_term',
                'label' => '14 الی 16 عصر',
                'value' => '14_to_16_afternoon',
                'sort_order' => 3,
                'is_active' => true,
                'start_time' => '14:00:00'
            ],

            // گزینه‌های ساعت برای کوتاه مدت
            [
                'type' => 'time',
                'term_type' => 'short_term',
                'label' => '10 صبح به بعد',
                'value' => '10_am_onwards',
                'sort_order' => 1,
                'is_active' => true,
                'start_time' => '10:00:00'
            ],
            [
                'type' => 'time',
                'term_type' => 'short_term',
                'label' => '12 ظهر به بعد',
                'value' => '12_pm_onwards',
                'sort_order' => 2,
                'is_active' => true,
                'start_time' => '12:00:00'
            ],
            [
                'type' => 'time',
                'term_type' => 'short_term',
                'label' => '14 الی 16 عصر',
                'value' => '14_to_16_afternoon',
                'sort_order' => 3,
                'is_active' => true,
                'start_time' => '14:00:00'
            ],
        ];

        foreach ($schedules as $schedule) {
            ServiceSchedule::create($schedule);
        }
    }
}
