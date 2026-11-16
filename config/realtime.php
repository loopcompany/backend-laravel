<?php

return [
    'enabled' => env('REALTIME_ENABLED', false),
    'host' => env('REALTIME_HOST', parse_url(env('APP_URL', ''), PHP_URL_HOST)),
    'port' => (int) env('REALTIME_PORT', 443),
    'scheme' => env('REALTIME_SCHEME', 'wss'),
];
