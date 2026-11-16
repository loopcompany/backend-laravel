@php
    $notes = $order->details->filter(fn ($detail) => filled($detail->user_descriptions));
@endphp
@if (filled($order->des) || $notes->isNotEmpty())
    <div style="margin-block:12px; padding:12px; border:1px solid #cbd5e1; border-radius:8px; text-align:right; direction:rtl; overflow-wrap:anywhere">
        <strong>توضیحات ثبت‌شدهٔ درخواست</strong>
        @if (filled($order->des))
            <div style="white-space:pre-line; margin-top:8px">{{ $order->des }}</div>
        @endif
        @foreach ($notes as $detail)
            <div style="margin-top:8px">
                <strong>{{ $detail->field?->title }} — {{ $detail->fieldDetail?->title }}</strong>
                <div style="white-space:pre-line">{{ $detail->user_descriptions }}</div>
            </div>
        @endforeach
    </div>
@endif
