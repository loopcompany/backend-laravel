<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * خطای کسب‌وکاری فرم همکاری با لوپ (پیام فارسی برای نمایش به متقاضی).
 */
class CooperationRequestException extends RuntimeException
{
    public function __construct(string $message, public readonly string $errorCode)
    {
        parent::__construct($message);
    }
}
