@php
    use App\Models\OrderStageLog;
    use App\Models\OrderWorkflow;
    use Morilog\Jalali\Jalalian;

    $logs = $getRecord()->stageLogs()->get();
    $actorColors = [
        'admin' => ['#eff6ff', '#1e40af'], 'user' => ['#f0fdf4', '#166534'],
        'technician' => ['#fffbeb', '#92400e'], 'system' => ['#f1f5f9', '#475569'],
    ];
@endphp

<style>
    .oh-list { position: relative; padding-right: 18px; }
    .oh-list::before { content: ''; position: absolute; right: 5px; top: 6px; bottom: 6px; width: 2px; background: #e2e8f0; }
    .oh-item { position: relative; padding: 0 0 14px 0; }
    .oh-item::before { content: ''; position: absolute; right: -17px; top: 6px; width: 10px; height: 10px; border-radius: 9999px; background: #94a3b8; border: 2px solid #fff; }
    .oh-item.has-stage::before { background: #2563eb; }
    .oh-head { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; font-size: 13px; }
    .oh-title { font-weight: 700; color: #1e293b; }
    .oh-badge { display: inline-block; padding: 1px 8px; border-radius: 9999px; font-size: 11px; font-weight: 600; }
    .oh-stage { font-size: 11.5px; color: #2563eb; }
    .oh-time { font-size: 11.5px; color: #64748b; margin-inline-start: auto; font-variant-numeric: tabular-nums; }
    .oh-note { font-size: 12.5px; color: #475569; margin-top: 3px; line-height: 1.7; white-space: pre-line; }
    .oh-empty { text-align: center; color: #94a3b8; padding: 16px; font-size: 13px; }
</style>

@if ($logs->isEmpty())
    <div class="oh-empty">برای این سفارش تاریخچه‌ای ثبت نشده است (سفارش قبل از فرآیند جدید ثبت شده).</div>
@else
    <div class="oh-list">
        @foreach ($logs as $log)
            @php [$bg, $fg] = $actorColors[$log->actor_type] ?? $actorColors['system']; @endphp
            <div class="oh-item {{ $log->to_stage ? 'has-stage' : '' }}">
                <div class="oh-head">
                    <span class="oh-title">{{ OrderStageLog::ACTIONS[$log->action] ?? $log->action }}</span>
                    <span class="oh-badge" style="background: {{ $bg }}; color: {{ $fg }};">
                        {{ OrderStageLog::ACTOR_TYPES[$log->actor_type] ?? $log->actor_type }}{{ $log->actor_name ? ': ' . $log->actor_name : '' }}
                    </span>
                    @if ($log->to_stage)
                        <span class="oh-stage">
                            {{ $log->from_stage ? OrderWorkflow::stageLabel($log->from_stage) . ' ← ' : '' }}{{ OrderWorkflow::stageLabel($log->to_stage) }}
                        </span>
                    @endif
                    <span class="oh-time">{{ $log->created_at ? Jalalian::forge($log->created_at)->format('Y/m/d H:i') : '' }}</span>
                </div>
                @if ($log->note)
                    <div class="oh-note">{{ $log->note }}</div>
                @endif
            </div>
        @endforeach
    </div>
@endif
