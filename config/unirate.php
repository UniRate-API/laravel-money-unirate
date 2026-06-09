<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Key
    |--------------------------------------------------------------------------
    |
    | Your UniRate API key. Get a free one at https://unirateapi.com.
    |
    */

    'api_key' => env('UNIRATE_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | Override for testing or self-hosted instances.
    |
    */

    'base_url' => env('UNIRATE_API_BASE_URL', 'https://api.unirateapi.com'),

    /*
    |--------------------------------------------------------------------------
    | Default base currency
    |--------------------------------------------------------------------------
    |
    | The base currency used when fetching a rate snapshot.
    |
    */

    'base_currency' => env('UNIRATE_BASE_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Rate responses are cached to avoid redundant API calls.
    | Set 'ttl' to 0 to disable caching entirely.
    |
    */

    'cache' => [
        'store' => env('UNIRATE_CACHE_STORE'),
        'ttl' => (int) env('UNIRATE_CACHE_TTL', 3600),
        'prefix' => 'unirate',
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP timeout (seconds)
    |--------------------------------------------------------------------------
    */

    'timeout' => (int) env('UNIRATE_TIMEOUT', 10),

];
