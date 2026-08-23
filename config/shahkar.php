<?php

return [

    // Zibal facility API base URL (docs.zibal.ir/facilities)
    'base_url' => env('SHAHKAR_BASE_URL', 'https://api.zibal.ir/v1'),

    // Access token from Zibal panel > Account > Developers > API Tokens
    'access_token' => env('SHAHKAR_ACCESS_TOKEN', ''),

    'timeout' => env('SHAHKAR_TIMEOUT', 15),
];
