<?php

namespace App\Support;

/**
 * minishlink/web-push expects VAPID keys as Base64Url (RFC 4648 §5) without padding,
 * matching `npx web-push generate-vapid-keys`. Common .env mistakes break decoding:
 * wrapping quotes, spaces/newlines, standard Base64 (+ /) instead of URL (- _).
 */
final class VapidKeyNormalizer
{
    public static function normalize(string $value): string
    {
        $value = trim($value, " \t\n\r\0\x0B");

        if ($value === '') {
            return '';
        }

        // Strip one pair of wrapping quotes often accidentally stored in .env
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
            $value = trim($value);
        }

        // Standard Base64 → Base64Url (if user pasted + and /)
        $value = strtr($value, '+/', '-_');
        // Remove padding (VAPID keys from web-push are typically unpadded)
        $value = rtrim($value, '=');

        return $value;
    }
}
