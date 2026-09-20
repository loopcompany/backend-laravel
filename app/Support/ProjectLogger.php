<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/** Centralized, redacting logger for business and security events. */
final class ProjectLogger
{
    public const APP = 'app';
    public const SECURITY = 'security';
    public const PAYMENTS = 'payments';
    public const FIREBASE = 'firebase';
    public const ENGAGEMENT = 'engagement';

    private const SENSITIVE_PARTS = [
        'password', 'passwd', 'token', 'secret', 'private_key', 'credentials',
        'authorization', 'cookie', 'api_key', 'access_key', 'refresh_key',
        'verification_code',
    ];

    public static function write(string $channel, string $level, string $message, array $context = []): void
    {
        $level = in_array($level, ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'], true)
            ? $level
            : 'info';

        Log::channel($channel)->{$level}($message, self::redact($context));
    }

    /** @param mixed $value */
    public static function redact(mixed $value, ?string $key = null): mixed
    {
        if ($key !== null && self::isSensitiveKey($key)) {
            return '[REDACTED]';
        }

        if (is_array($value)) {
            $result = [];
            foreach (array_slice($value, 0, 50, true) as $childKey => $childValue) {
                $result[(string) $childKey] = self::redact($childValue, (string) $childKey);
            }

            return $result;
        }

        if (is_string($value) && strlen($value) > 2000) {
            return substr($value, 0, 2000) . '…';
        }

        return $value;
    }

    private static function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower(str_replace(['-', ' '], '_', $key));

        foreach (self::SENSITIVE_PARTS as $part) {
            if (str_contains($normalized, $part)) {
                return true;
            }
        }

        return false;
    }
}
