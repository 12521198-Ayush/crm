<?php

return [
    'meta' => [
        'app_id' => env('META_APP_ID'),
        'app_secret' => env('META_APP_SECRET'),
        'verify_token' => env('META_VERIFY_TOKEN'),
        'page_access_token' => env('META_PAGE_ACCESS_TOKEN'),
    ],
    'google_ads' => [
        'webhook_token' => env('GOOGLE_ADS_WEBHOOK_TOKEN'),
    ],
    'ivr' => [
        'webhook_token' => env('IVR_WEBHOOK_TOKEN'),
    ],
];
