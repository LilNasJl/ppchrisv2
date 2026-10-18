<?php

return [
    'url' => rtrim((string) env('BIOMETRICS_SYNC_URL', ''), '/'),
    'secret' => env('BIOMETRICS_SYNC_SECRET', ''),
    'callback_url' => env('BIOMETRICS_SYNC_CALLBACK_URL', ''),
    'pairing_token' => env('BIOMETRICS_PAIRING_TOKEN', ''),
    'max_bytes' => 10 * 1024 * 1024,
    'max_records' => 10000,
];
