<?php

namespace App\Services;

use App\Models\ContactUs;
use Exception;
use Illuminate\Support\Facades\Log;

class ContactService
{
    /**
     * ذخیره پیام تماس جدید
     */
    public function submitContactMessage(array $data): array
    {
        try {
            // اعتبارسنجی اضافی اگر لازم باشد
            $this->validateContactData($data);

            // ذخیره پیام در دیتابیس
            $contact = ContactUs::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'title' => $data['title'],
                'message' => $data['message'],
            ]);

            Log::info('Contact message submitted successfully', [
                'contact_id' => $contact->id,
                'email' => $data['email'],
                'name' => $data['name']
            ]);

            return [
                'success' => true,
                'message' => 'پیام شما با موفقیت ارسال شد. کارشناسان ما در اسرع وقت با شما تماس خواهند گرفت.',
                'contact_id' => $contact->id,
            ];

        } catch (Exception $e) {
            Log::error('Failed to submit contact message', [
                'error' => $e->getMessage(),
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ارسال پیام. لطفاً مجدداً تلاش کنید.',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * اعتبارسنجی اضافی داده‌های تماس
     */
    protected function validateContactData(array $data): void
    {
        // اعتبارسنجی‌های خاص business logic
        if (empty(trim($data['name']))) {
            throw new Exception('نام نمی‌تواند خالی باشد');
        }

        if (strlen($data['message']) < 10) {
            throw new Exception('پیام باید حداقل 10 کاراکتر باشد');
        }

        // بررسی spam (اختیاری)
        if ($this->isSpamMessage($data['message'])) {
            throw new Exception('پیام شما به عنوان spam شناسایی شد');
        }
    }

    /**
     * بررسی spam بودن پیام
     */
    protected function isSpamMessage(string $message): bool
    {
        // الگوریتم ساده برای تشخیص spam
        $spamKeywords = ['spam', 'promotion', 'click here', 'buy now'];
        
        foreach ($spamKeywords as $keyword) {
            if (stripos($message, $keyword) != false) {
                return true;
            }
        }

        return false;
    }

    /**
     * دریافت آمار پیام‌های تماس
     */
    public function getContactStats(): array
    {
        return [
            'total_messages' => ContactUs::count(),
            'today_messages' => ContactUs::whereDate('created_at', today())->count(),
            'pending_messages' => ContactUs::where('status', 0)->count(),
        ];
    }

    /**
     * دریافت پیام‌های اخیر
     */
    public function getRecentMessages(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return ContactUs::latest()->limit($limit)->get();
    }
}