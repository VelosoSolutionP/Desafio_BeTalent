<?php

return [
    'gateway1' => [
        'url'   => env('GATEWAY1_URL', 'http://localhost:3001'),
        'email' => env('GATEWAY1_EMAIL'),
        'token' => env('GATEWAY1_TOKEN'),
    ],

    'gateway2' => [
        'url'         => env('GATEWAY2_URL', 'http://localhost:3002'),
        'auth_token'  => env('GATEWAY2_AUTH_TOKEN'),
        'auth_secret' => env('GATEWAY2_AUTH_SECRET'),
    ],

    'mailgun' => [
        'domain'   => env('MAILGUN_DOMAIN'),
        'secret'   => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme'   => 'https',
    ],
];
