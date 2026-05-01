<?php

declare(strict_types=1);

namespace App\Support;

class ApiPayloadSanitizer
{
    /**
     * @var string[]
     */
    private const SENSITIVE_KEYS = [
        'password',
        'token',
        'api_key',
        'webhook_secret',
        'authorization',
        'secret',
    ];

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public static function sanitize(array $payload): array
    {
        return self::sanitizeRecursive($payload);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private static function sanitizeRecursive(array $payload): array
    {
        foreach ($payload as $key => $value) {
            $normalized = strtolower((string) $key);
            if (in_array($normalized, self::SENSITIVE_KEYS, true)) {
                $payload[$key] = '***';
                continue;
            }

            if (is_array($value)) {
                $payload[$key] = self::sanitizeRecursive($value);
            }
        }

        return $payload;
    }
}
