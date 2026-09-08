<?php

return [
    'provider' => env('MARKET_DATA_PROVIDER', 'zapi'),
    'poll_seconds' => (int) env('MARKET_DATA_POLL_SECONDS', 900),
    'only_open_session' => filter_var(env('MARKET_DATA_ONLY_OPEN_SESSION', true), FILTER_VALIDATE_BOOL),
    'zapi' => [
        'base_url' => env('ZPI_BASE_URL', 'https://api.zpi.web.id/v1/finance:idx'),
        'api_key' => env('ZPI_API_KEY'),
        'timeout' => (int) env('ZPI_TIMEOUT', 3),
        'connect_timeout' => (int) env('ZPI_CONNECT_TIMEOUT', 2),
    ],
];
