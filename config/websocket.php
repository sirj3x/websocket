<?php

return [
    // Websocket address
    'ip' => env('WEBSOCKET_IP', '127.0.0.1'),
    'port' => env('WEBSOCKET_PORT', 8443),

    // log path
    'log_path' => base_path('websocket-worker.log'),

    // Websocket push to client settings
    // !!! [IMPORTANT NOTICE] This port should be closed in the firewall. Only used locally.
    'ptc_channel_ip' => env('WEBSOCKET_PTC_CHANNEL_IP', '127.0.0.1'),
    'ptc_channel_port' => env('WEBSOCKET_PTC_CHANNEL_PORT', 2206),
    'ptc_tcp_ip' => env('WEBSOCKET_PTC_TCP_IP', '127.0.0.1'),
    'ptc_tcp_port' => env('WEBSOCKET_PTC_TCP_PORT', 2207),

    // Websocket ssl transport
    'transport_ssl' => false,

    // Websocket context
    'context' => [
        'ssl' => [
            'local_cert' => base_path('ssl/ssl.cert'),
            'local_pk' => base_path('ssl/ssl.key'),
            'verify_peer' => false,
            'allow_self_signed' => false, // Allow self-signed certs (should be false in production)
            'crypto_method' => STREAM_CRYPTO_METHOD_TLS_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,
        ]
    ],

    'middleware' => [
        //
    ]
];
