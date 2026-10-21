{{-- رنگ مراحل سفارش در لیست سفارشات: نوار کنار ردیف (هم‌رنگ تب و نشان وضعیت) --}}
<style>
    @foreach (\App\Models\OrderWorkflow::STAGE_HEX as $stage => [$main, $soft])
        .fi-ta-row.ow-row-{{ $stage }} > td:first-child { box-shadow: inset -4px 0 0 {{ $main }}; }
        .fi-ta-row.ow-row-{{ $stage }}:hover > td { background-color: {{ $soft }}; }
    @endforeach
</style>
