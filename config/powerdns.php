<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PowerDNS Operation Mode
    |--------------------------------------------------------------------------
    |
    | Supported modes:
    | - "database": Direct DB read/write to PowerDNS gmysql tables (Fastest & direct)
    | - "api": PowerDNS Built-in HTTP API (via webserver/pdns API endpoint)
    | - "hybrid": Database operations with API notifications/cache purge
    |
    */
    'mode' => env('PDNS_MODE', 'database'),

    /*
    |--------------------------------------------------------------------------
    | PowerDNS HTTP API Configuration
    |--------------------------------------------------------------------------
    */
    'api' => [
        'url' => env('PDNS_API_URL', 'http://127.0.0.1:8081'),
        'key' => env('PDNS_API_KEY', ''),
        'server_id' => env('PDNS_SERVER_ID', 'localhost'),
        'timeout' => (int) env('PDNS_API_TIMEOUT', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default DNS Settings
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'ttl' => (int) env('PDNS_DEFAULT_TTL', 3600),
        'nameservers' => array_filter(explode(',', env('PDNS_DEFAULT_NAMESERVERS', 'ns1.example.com,ns2.example.com'))),
        'soa' => [
            'primary_ns' => env('PDNS_SOA_PRIMARY_NS', 'ns1.example.com'),
            'hostmaster' => env('PDNS_SOA_HOSTMASTER', 'hostmaster.example.com'),
            'refresh' => (int) env('PDNS_SOA_REFRESH', 10800),
            'retry' => (int) env('PDNS_SOA_RETRY', 3600),
            'expire' => (int) env('PDNS_SOA_EXPIRE', 604800),
            'minimum' => (int) env('PDNS_SOA_MINIMUM', 3600),
        ],
    ],
];
