@php
    use App\Filament\Resources\OrderResource\Workflow\OrderWorkflowPanel;
    use App\Models\OrderWorkflow;

    $order = $getRecord();
    $steps = OrderWorkflowPanel::steps($order);
    $stage = OrderWorkflowPanel::workflow()->stage($order);
    $details = OrderWorkflowPanel::workflow()->details($order);

    $total = count($steps);
    $passed = collect($steps)->filter(fn ($s) => in_array($s['state'], ['done', 'skipped'], true))->count();
    $isFinished = $stage === OrderWorkflow::STAGE_CLOSED && (int) $order->status === 2;
    $percent = $isFinished ? 100 : (int) round(($passed / max(1, $total - 1)) * 100);
    $currentIndex = collect($steps)->search(fn ($s) => in_array($s['state'], ['current', 'cancelled'], true));
    [$stageMain, $stageSoft, $stageDark] = OrderWorkflow::STAGE_HEX[$stage] ?? OrderWorkflow::STAGE_HEX['registered'];
@endphp

<style>
    .ows { --ows-gray: #cbd5e1; --ows-muted: #94a3b8; }
    .ows-head { display: flex; flex-wrap: wrap; align-items: center; gap: 12px 20px; margin-bottom: 22px; }
    .ows-title { font-size: 15px; font-weight: 800; color: #0f172a; }
    .ows-chip { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 9999px; font-size: 12.5px; font-weight: 700; }
    .ows-chip svg { width: 16px; height: 16px; }
    .ows-progress { flex: 1 1 220px; display: flex; align-items: center; gap: 10px; min-width: 200px; }
    .ows-bar { flex: 1; height: 8px; border-radius: 9999px; background: #e2e8f0; overflow: hidden; }
    .ows-bar > span { display: block; height: 100%; border-radius: 9999px; transition: width .4s; }
    .ows-pct { font-size: 12px; font-weight: 700; color: #475569; white-space: nowrap; font-variant-numeric: tabular-nums; }

    .ows-track { display: grid; grid-template-columns: repeat({{ $total }}, minmax(0, 1fr)); gap: 0; }
    .ows-step { position: relative; display: flex; flex-direction: column; align-items: center; text-align: center; padding: 0 4px; }
    .ows-line { position: absolute; top: 24px; height: 4px; width: 100%; right: 50%; z-index: 0; border-radius: 9999px; background: var(--ows-gray); }
    .ows-step:first-child .ows-line { display: none; }
    .ows-line.is-dashed { background: repeating-linear-gradient(-90deg, var(--ows-gray) 0 6px, transparent 6px 11px); }

    .ows-node { position: relative; z-index: 1; width: 48px; height: 48px; border-radius: 16px; display: flex; align-items: center; justify-content: center;
        background: #fff; border: 2px solid var(--ows-gray); color: var(--ows-muted); transition: transform .2s; }
    .ows-node svg { width: 22px; height: 22px; }
    .ows-badge { position: absolute; bottom: -6px; left: -6px; width: 20px; height: 20px; border-radius: 9999px; display: flex; align-items: center; justify-content: center;
        font-size: 11px; font-weight: 800; color: #fff; border: 2px solid #fff; }
    .ows-num { position: absolute; top: -8px; right: -8px; min-width: 20px; height: 20px; padding: 0 5px; border-radius: 9999px; display: flex; align-items: center; justify-content: center;
        font-size: 10.5px; font-weight: 800; background: #fff; color: var(--ows-muted); border: 1.5px solid var(--ows-gray); }

    .ows-step.is-current .ows-node { transform: scale(1.12); }
    .ows-step.is-skipped .ows-node { border-style: dashed; background: #f8fafc; }
    .ows-step.is-upcoming .ows-node { background: #f8fafc; }

    .ows-label { margin-top: 12px; font-size: 12.5px; font-weight: 700; line-height: 1.55; color: #334155; }
    .ows-step.is-upcoming .ows-label, .ows-step.is-skipped .ows-label { color: var(--ows-muted); font-weight: 600; }
    .ows-hint { margin-top: 3px; font-size: 11px; line-height: 1.5; color: #64748b; }
    .ows-step.is-skipped .ows-hint, .ows-step.is-upcoming .ows-hint { color: var(--ows-muted); }
    .ows-now { margin-top: 6px; display: inline-block; padding: 3px 9px; border-radius: 8px; font-size: 11px; font-weight: 700; line-height: 1.5; max-width: 100%; }

    .ows-banner { margin-top: 20px; display: flex; gap: 10px; align-items: flex-start; padding: 12px 14px; border-radius: 12px; font-size: 13px; line-height: 1.8; border: 1px solid; }
    .ows-banner svg { width: 20px; height: 20px; flex-shrink: 0; margin-top: 2px; }

    @media (max-width: 1024px) {
        .ows-track { grid-template-columns: 1fr; gap: 14px; }
        .ows-step { flex-direction: row; align-items: center; text-align: right; gap: 14px; padding: 0; }
        .ows-line { top: -14px; right: 22px; width: 4px; height: calc(50% - 10px); }
        .ows-line.is-dashed { background: repeating-linear-gradient(180deg, var(--ows-gray) 0 6px, transparent 6px 11px); }
        .ows-node { flex-shrink: 0; }
        .ows-step.is-current .ows-node { transform: none; }
        .ows-label { margin-top: 0; }
        .ows-text { flex: 1; }
    }
</style>

<div class="ows">
    <div class="ows-head">
        <span class="ows-title">مراحل سفارش #{{ $order->id }}</span>
        <span class="ows-chip" style="background: {{ $stageSoft }}; color: {{ $stageDark }}; box-shadow: inset 0 0 0 1px {{ $stageMain }}40;">
            <x-filament::icon :icon="OrderWorkflow::STAGE_ICONS[$stage] ?? 'heroicon-o-flag'" />
            {{ $isFinished ? 'پرونده مختومه' : OrderWorkflow::stageLabel($stage) }}
        </span>
        <div class="ows-progress">
            <div class="ows-bar"><span style="width: {{ $percent }}%; background: linear-gradient(-90deg, {{ $stageMain }}, {{ $stageMain }}cc);"></span></div>
            <span class="ows-pct">
                @if ($currentIndex !== false && !$isFinished)
                    مرحله {{ $currentIndex + 1 }} از {{ $total }} —
                @endif
                {{ $percent }}٪
            </span>
        </div>
    </div>

    <div class="ows-track">
        @foreach ($steps as $index => $step)
            @php
                [$main, $soft, $dark] = OrderWorkflow::STAGE_HEX[$step['stage']] ?? OrderWorkflow::STAGE_HEX['registered'];
                $state = $step['state'];
                $prevState = $index > 0 ? $steps[$index - 1]['state'] : null;
                $lineColor = in_array($state, ['done', 'current', 'cancelled'], true) ? $main : null;
                $icon = OrderWorkflow::STAGE_ICONS[$step['stage']] ?? 'heroicon-o-flag';
                if ($state === 'cancelled') { [$main, $soft, $dark] = OrderWorkflow::STAGE_HEX[OrderWorkflow::STAGE_CANCELLED]; }
                $nodeStyle = match ($state) {
                    'done' => "background: {$main}; border-color: {$main}; color: #fff;",
                    'current' => "background: {$soft}; border-color: {$main}; color: {$main}; box-shadow: 0 0 0 6px {$main}26, 0 8px 18px -6px {$main}80;",
                    'cancelled' => "background: {$soft}; border-color: {$main}; color: {$main}; box-shadow: 0 0 0 6px {$main}26;",
                    default => '',
                };
            @endphp

            <div class="ows-step is-{{ $state }}">
                <span class="ows-line {{ $state === 'skipped' ? 'is-dashed' : '' }}" @if ($lineColor) style="background: {{ $lineColor }};" @endif></span>

                <div class="ows-node" style="{{ $nodeStyle }}">
                    <x-filament::icon :icon="$state === 'cancelled' ? 'heroicon-o-x-circle' : $icon" />
                    <span class="ows-num" @if (in_array($state, ['done', 'current'], true)) style="color: {{ $dark }}; border-color: {{ $main }};" @endif>{{ $index + 1 }}</span>
                    @if ($state === 'done')
                        <span class="ows-badge" style="background: {{ OrderWorkflow::STAGE_HEX[OrderWorkflow::STAGE_CLOSED][0] }};">✓</span>
                    @elseif ($state === 'skipped')
                        <span class="ows-badge" style="background: var(--ows-muted);">–</span>
                    @endif
                </div>

                <div class="ows-text">
                    <div class="ows-label" @if (in_array($state, ['current', 'cancelled'], true)) style="color: {{ $dark }};" @endif>{{ $step['label'] }}</div>
                    @if (in_array($state, ['current', 'cancelled'], true) && $step['hint'])
                        <span class="ows-now" style="background: {{ $soft }}; color: {{ $dark }};">{{ $step['hint'] }}</span>
                    @elseif ($step['hint'])
                        <div class="ows-hint">{{ $step['hint'] }}</div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    @if ($stage === OrderWorkflow::STAGE_CANCELLED)
        @php [$m, $s, $d] = OrderWorkflow::STAGE_HEX[OrderWorkflow::STAGE_CANCELLED]; @endphp
        <div class="ows-banner" style="background: {{ $s }}; color: {{ $d }}; border-color: {{ $m }}40;">
            <x-filament::icon icon="heroicon-o-x-circle" />
            <div>
                سفارش لغو شده است — {{ OrderWorkflow::CANCEL_REASONS[$details->cancel_reason_type] ?? 'علت ثبت نشده' }}@if ($details->cancel_reason): {{ $details->cancel_reason }}@endif
                @if ($details->device_return_required && !$order->returned_at)
                    <br><strong>دستگاه هنوز عودت داده نشده است؛ پس از عودت، پرونده‌ی لغو تکمیل می‌شود.</strong>
                @endif
            </div>
        </div>
    @elseif ($stage === OrderWorkflow::STAGE_REVIEW && $details->review_status === 'incomplete')
        @php [$m, $s, $d] = OrderWorkflow::STAGE_HEX[OrderWorkflow::STAGE_REVIEW]; @endphp
        <div class="ows-banner" style="background: {{ $s }}; color: {{ $d }}; border-color: {{ $m }}40;">
            <x-filament::icon icon="heroicon-o-exclamation-triangle" />
            <div>اطلاعات سفارش ناقص است و تا تکمیل، تخصیص تکنسین انجام نمی‌شود: {{ $details->review_note }}</div>
        </div>
    @elseif ($isFinished)
        @php [$m, $s, $d] = OrderWorkflow::STAGE_HEX[OrderWorkflow::STAGE_CLOSED]; @endphp
        <div class="ows-banner" style="background: {{ $s }}; color: {{ $d }}; border-color: {{ $m }}40;">
            <x-filament::icon icon="heroicon-o-check-badge" />
            <div>پرونده مختومه شده است — {{ OrderWorkflow::FIELD_RESULTS[$details->field_result] ?? '' }}</div>
        </div>
    @endif
</div>
