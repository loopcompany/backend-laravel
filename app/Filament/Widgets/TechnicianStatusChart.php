<?php

namespace App\Filament\Widgets;

use App\Models\Technician; // اگر مدل شما چیز دیگری است عوضش کن
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
class TechnicianStatusChart extends ChartWidget
{
    protected static ?string $heading = 'وضعیت تکنسین‌ها';
    protected static ?int $sort = 4;

    protected function getData(): array
    {
        // اگر همه رکوردها تکنسین نیستند، اینجا فیلتر تکنسین‌ها را اضافه کن
        // مثال: ->where('role', 'technician')
        if (!Auth::user()?->can('view-dashboard')) {
            return []; // داشبورد خالی
        }
        $row = Technician::query()
            ->selectRaw("
                SUM(CASE WHEN is_leave = 1 THEN 1 ELSE 0 END) as leave_count,
                SUM(CASE WHEN has_access = 0 THEN 1 ELSE 0 END) as suspended_count,
                SUM(CASE WHEN is_absent = 1 THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN approval_status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                COUNT(*) as total_count
            ")
            ->first();

        $leave = (int) ($row->leave_count ?? 0);
        $suspended = (int) ($row->suspended_count ?? 0);
        $absent = (int) ($row->absent_count ?? 0);
        $pending = (int) ($row->pending_count ?? 0);
        $total = (int) ($row->total_count ?? 0);

        $others = max(0, $total - ($leave + $suspended + $absent + $pending));

        return [
            'datasets' => [
                [
                    'label' => 'تعداد تکنسین‌ها',
                    'data' => [$leave, $suspended, $absent, $pending, $others],
                    'backgroundColor' => [
                        '#f59e0b', // مرخصی
                        '#ef4444', // تعلیق
                        '#3b82f6', // عدم حضور
                        '#a855f7', // در انتظار تایید
                        '#10b981', // سایر
                    ],
                ],
            ],
            'labels' => ['مرخصی', 'تعلیق', 'عدم حضور', 'در انتظار تایید', 'سایر'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
