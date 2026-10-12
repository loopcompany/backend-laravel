<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // دیتابیس GeoIP محلی (mmdb) برای «موقعیت تقریبی» دستگاه‌ها؛ با `php artisan geoip:update` دانلود می‌شود
    'geoip' => [
        'database' => env('GEOIP_DATABASE_PATH', storage_path('app/geoip/dbip-city-lite.mmdb')),
        'download_url' => env('GEOIP_DOWNLOAD_URL', 'https://download.db-ip.com/free/dbip-city-lite-{Y}-{m}.mmdb.gz'),
    ],

    // فهرست رسمی دستگاه‌های Google Play برای نام تجاری دستگاه (`php artisan devices:import-names`)
    'device_names' => [
        'csv_url' => env('DEVICE_NAMES_CSV_URL', 'https://storage.googleapis.com/play_public/supported_devices.csv'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
