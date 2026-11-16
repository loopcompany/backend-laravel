@auth('admin')
    <script type="application/json" id="loop-realtime-config">@json(app(\App\Http\Controllers\RealtimeController::class)->adminConfig())</script>
    <script src="{{ asset('assets/js/loop-realtime.js') }}" defer></script>
    <script src="{{ asset('assets/js/loop-admin-realtime.js') }}" defer></script>
@endauth
