<?php

namespace Tests\Fakes;

use App\Services\SmsService;

/**
 * پیامک واقعی نمی‌فرستد؛ کدها را برای خواندن در تست نگه می‌دارد.
 */
class FakeSmsService extends SmsService
{
    /** @var array<int, array{phone: string, code: string}> */
    public array $sent = [];

    public bool $fail = false;

    public function sendVerificationCode(string $phone, string $code, ?string $hashApp = ''): bool
    {
        if ($this->fail) {
            return false;
        }

        $this->sent[] = ['phone' => $phone, 'code' => $code];

        return true;
    }

    public function lastCodeFor(string $phone): ?string
    {
        foreach (array_reverse($this->sent) as $sms) {
            if ($sms['phone'] === $phone) {
                return $sms['code'];
            }
        }

        return null;
    }
}
