<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging
    |--------------------------------------------------------------------------
    |
    | The service account is intentionally read only on the backend. Never
    | expose FIREBASE_CREDENTIALS_PATH/JSON to a client application.
    |
    */

    'enabled' => (bool) env('FIREBASE_ENABLED', false),

    'project_id' => env('FIREBASE_PROJECT_ID'),

    // Prefer a file outside the public directory in production.
    'credentials_path' => env('FIREBASE_CREDENTIALS_PATH'),

    // Useful for containers/secrets managers when a file is not available.
    'credentials_json' => env('FIREBASE_CREDENTIALS_JSON'),

    'timeout' => (int) env('FIREBASE_HTTP_TIMEOUT', 15),

];
