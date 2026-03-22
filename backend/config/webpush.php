<?php

use App\Support\VapidKeyNormalizer;

return [
    /*
    |--------------------------------------------------------------------------
    | VAPID keys for Web Push (RFC 8292)
    |--------------------------------------------------------------------------
    |
    | Generate keys once:
    |   npx web-push generate-vapid-keys
    | Paste the two lines into .env as ONE line each, NO spaces, usually NO quotes:
    |   VAPID_PUBLIC_KEY=...
    |   VAPID_PRIVATE_KEY=...
    | If you see "Private key should be 32 bytes": keys are swapped, truncated, or not Base64Url.
    |
    | VAPID_SUBJECT is a mailto: or https: URL (see Web Push spec).
    |
    */
    'public_key' => VapidKeyNormalizer::normalize((string) env('VAPID_PUBLIC_KEY', '')),
    'private_key' => VapidKeyNormalizer::normalize((string) env('VAPID_PRIVATE_KEY', '')),
    'subject' => env('VAPID_SUBJECT', 'mailto:admin@example.com'),

    /** Base URL of the React app (used to turn relative notification URLs into absolute links). */
    'frontend_url' => rtrim((string) env('FRONTEND_URL', env('APP_URL', 'http://localhost:8080')), '/'),
];
