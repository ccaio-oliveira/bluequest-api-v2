<?php

return [
    'google' => [
        'jwks_url' => 'https://www.googleapis.com/oauth2/v3/certs',
        'issuers' => ['https://accounts.google.com', 'accounts.google.com'],
        'audiences' => array_filter([env('GOOGLE_IOS_CLIENT_ID')]),
    ],

    'apple' => [
        'jwks_url' => 'https://appleid.apple.com/auth/keys',
        'issuers' => ['https://appleid.apple.com'],
        'audiences' => array_filter([env('APPLE_BUNDLE_ID')]),
    ],
];
