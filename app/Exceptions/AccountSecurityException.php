<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * خطای کسب‌وکاری امنیت حساب با پیام فارسی و error_code؛ مستقیم به پاسخ JSON تبدیل می‌شود.
 */
class AccountSecurityException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $errorCode,
        public readonly int $status = 422,
        public readonly array $data = [],
    ) {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json(array_filter([
            'success' => false,
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
            'data' => $this->data ?: null,
        ], fn ($v) => $v !== null), $this->status);
    }
}
