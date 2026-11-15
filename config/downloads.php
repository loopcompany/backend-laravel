<?php

return [
    // Store URLs are supplied by the product owner; never substitute a store homepage.
    'user' => [
        'bazaar' => env('USER_BAZAAR_URL'),
        'myket' => env('USER_MYKET_URL'),
        'ios' => env('USER_IOS_URL'),
    ],
    'technician' => [
        'bazaar' => env('TECHNICIAN_BAZAAR_URL'),
        'myket' => env('TECHNICIAN_MYKET_URL'),
        'ios' => env('TECHNICIAN_IOS_URL'),
    ],
];
