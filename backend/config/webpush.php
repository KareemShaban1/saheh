<?php

return [
    /*
    |--------------------------------------------------------------------------
    | VAPID keys for Web Push (RFC 8292)
    |--------------------------------------------------------------------------
    |
    | Generate keys once:
    |   php -r "require 'vendor/autoload.php'; print_r(Minishlink\WebPush\VAPID::createVapidKeys());"
    | Or: npx web-push generate-vapid-keys
    |
    | VAPID_SUBJECT is a mailto: or https: URL (see Web Push spec).
    |
    */
    'public_key' => env('VAPID_PUBLIC_KEY', ''),
    'private_key' => env('VAPID_PRIVATE_KEY', ''),
    'subject' => env('VAPID_SUBJECT', 'mailto:admin@example.com'),

    /** Base URL of the React app (used to turn relative notification URLs into absolute links). */
    'frontend_url' => rtrim((string) env('FRONTEND_URL', env('APP_URL', 'http://localhost:8080')), '/'),
];
