<?php

namespace App\Services;

use RuntimeException;

/** انتقال نامعتبر در فرآیند سفارش؛ پیام آن مستقیم به ادمین / اپ نمایش داده می‌شود. */
class OrderWorkflowException extends RuntimeException
{
    public function __construct(string $message, public readonly string $errorCode = 'WORKFLOW_INVALID')
    {
        parent::__construct($message);
    }
}
