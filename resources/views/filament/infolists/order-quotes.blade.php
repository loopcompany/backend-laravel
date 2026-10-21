@php
    use App\Models\OrderQuote;
    use Morilog\Jalali\Jalalian;

    $order = $getRecord();
    $quotes = $order->quotes()->whereIn('kind', $kinds ?? array_keys(OrderQuote::KINDS))->reorder('version', 'desc')->get();
    $colors = [
        'success' => ['#f0fdf4', '#166534'], 'danger' => ['#fef2f2', '#991b1b'], 'warning' => ['#fffbeb', '#92400e'],
        'info' => ['#eff6ff', '#1e40af'], 'gray' => ['#f1f5f9', '#475569'],
    ];
    $jdt = fn ($v) => $v ? Jalalian::forge($v)->format('Y/m/d H:i') : '-';
@endphp

<style>
    .oq-wrap { overflow-x: auto; }
    .oq-table { width: 100%; border-collapse: collapse; font-size: 12.5px; min-width: 760px; }
    .oq-table th { background: #f8fafc; color: #475569; font-weight: 600; text-align: right; padding: 8px 10px; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
    .oq-table td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; vertical-align: top; color: #1e293b; }
    .oq-table tr.is-muted td { color: #94a3b8; }
    .oq-badge { display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 11.5px; font-weight: 600; white-space: nowrap; }
    .oq-num { font-variant-numeric: tabular-nums; white-space: nowrap; }
    .oq-note { font-size: 11.5px; color: #64748b; margin-top: 3px; line-height: 1.6; }
    .oq-empty { text-align: center; color: #94a3b8; padding: 16px; font-size: 13px; }
</style>

<div class="oq-wrap">
    @if ($quotes->isEmpty())
        <div class="oq-empty">هنوز قیمتی ثبت نشده است.</div>
    @else
        <table class="oq-table">
            <thead>
                <tr>
                    <th>نسخه</th>
                    <th>نوع</th>
                    <th>اجرت</th>
                    <th>قطعات</th>
                    <th>اضافی</th>
                    <th>جمع</th>
                    <th>زمان</th>
                    <th>وضعیت</th>
                    <th>ثبت / ارسال</th>
                    <th>تصمیم</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($quotes as $quote)
                    @php [$bg, $fg] = $colors[OrderQuote::STATUS_COLORS[$quote->status] ?? 'gray']; @endphp
                    <tr class="{{ in_array($quote->status, ['superseded', 'discarded'], true) ? 'is-muted' : '' }}">
                        <td class="oq-num">{{ $quote->version }}</td>
                        <td>
                            {{ OrderQuote::KINDS[$quote->kind] ?? $quote->kind }}
                            @if ($quote->is_estimate)<div class="oq-note">برآورد مقدماتی</div>@endif
                        </td>
                        <td class="oq-num">{{ number_format($quote->service_fee) }}</td>
                        <td class="oq-num">{{ number_format($quote->parts_fee) }}</td>
                        <td class="oq-num">{{ number_format($quote->extra_fee) }}</td>
                        <td class="oq-num"><strong>{{ number_format($quote->total) }}</strong> تومان</td>
                        <td>
                            {{ $quote->estimated_duration ?: '-' }}
                            @if ($quote->visit_date)<div class="oq-note">مراجعه: {{ Jalalian::forge($quote->visit_date)->format('Y/m/d') }} {{ $quote->visit_time }}</div>@endif
                        </td>
                        <td><span class="oq-badge" style="background: {{ $bg }}; color: {{ $fg }};">{{ OrderQuote::STATUSES[$quote->status] ?? $quote->status }}</span></td>
                        <td>
                            {{ $quote->proposerName() }}
                            <div class="oq-note">{{ $jdt($quote->created_at) }}@if ($quote->sent_at) — ارسال {{ $jdt($quote->sent_at) }}@endif</div>
                        </td>
                        <td>
                            @if ($quote->decided_at)
                                {{ $quote->decided_by_type === 'user' ? 'کاربر (اپ)' : 'پنل' }} — {{ $jdt($quote->decided_at) }}
                            @else
                                -
                            @endif
                            @if ($quote->decision_note)<div class="oq-note">{{ $quote->decision_note }}</div>@endif
                        </td>
                    </tr>
                    @if ($quote->change_reason || $quote->parts_description || $quote->description)
                        <tr class="{{ in_array($quote->status, ['superseded', 'discarded'], true) ? 'is-muted' : '' }}">
                            <td></td>
                            <td colspan="9">
                                @if ($quote->change_reason)<div class="oq-note"><strong>علت تغییر:</strong> {{ $quote->change_reason }}</div>@endif
                                @if ($quote->parts_description)<div class="oq-note"><strong>قطعات:</strong> {{ $quote->parts_description }}</div>@endif
                                @if ($quote->description)<div class="oq-note"><strong>توضیحات:</strong> {{ $quote->description }}</div>@endif
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    @endif
</div>
