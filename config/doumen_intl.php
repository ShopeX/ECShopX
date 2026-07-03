<?php

return [
    'base_url' => env('DOUMEN_INTL_BASE_URL', ''),
    'notify_url' => env('DOUMEN_INTL_NOTIFY_URL', ''),
    'is_sandbox' => env('DOUMEN_INTL_SANDBOX', true),
    'token_refresh_interval_minutes' => (int) env('DOUMEN_INTL_TOKEN_REFRESH_INTERVAL_MINUTES', 115),
];
