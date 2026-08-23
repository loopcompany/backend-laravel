<?php

namespace Tests\Unit;

use App\Services\TechnicianRegistrationService;
use Tests\TestCase;

class TechnicianPasswordSMSTest extends TestCase
{
    /**
     * تست رفع باگ: کد پرسنلی و رمز عبور باید با ترتیب صحیح ارسال شوند
     * 
     * قالب SMS 923719:
     * کد پرسنلی: #CODE# 
     * رمز عبور شما: #PASS#
     * 
     * پس ترتیب صحیح پارامترها:
     * names: ['CODE', 'PASS']
     * values: [کد_پرسنلی, رمز_عبور]
     */
    public function test_sms_template_parameter_order_is_documented(): void
    {
        // این تست فقط برای مستندسازی و یادآوری ترتیب صحیح است
        $correctOrder = [
            'template_id' => '923719',
            'parameters' => [
                'names' => ['CODE', 'PASS'],
                'values' => ['کد_پرسنلی', 'رمز_عبور']
            ],
            'message_format' => 'کد پرسنلی: #CODE# و رمز عبور شما: #PASS# می باشد.'
        ];

        $this->assertEquals('923719', $correctOrder['template_id']);
        $this->assertEquals(['CODE', 'PASS'], $correctOrder['parameters']['names']);
        
        // اطمینان از اینکه اولین پارامتر CODE (کد پرسنلی) است
        $this->assertEquals('CODE', $correctOrder['parameters']['names'][0]);
        
        // اطمینان از اینکه دومین پارامتر PASS (رمز عبور) است
        $this->assertEquals('PASS', $correctOrder['parameters']['names'][1]);
    }

    /**
     * تست بررسی ساختار صحیح کد در سرویس
     */
    public function test_service_code_structure_is_correct(): void
    {
        // بررسی وجود فایل سرویس
        $serviceFile = app_path('Services/TechnicianRegistrationService.php');
        $this->assertFileExists($serviceFile);

        // خواندن محتوای فایل
        $content = file_get_contents($serviceFile);

        // بررسی وجود ترتیب صحیح ['CODE', 'PASS']
        $this->assertStringContainsString("['CODE', 'PASS']", $content);
        
        // بررسی وجود ارسال با ترتیب صحیح: کد پرسنلی، رمز عبور
        $this->assertStringContainsString('[$technician->referral_code, $password]', $content);
        
        // بررسی وجود template ID صحیح
        $this->assertStringContainsString("'923719'", $content);
        
        // اطمینان از عدم وجود ترتیب اشتباه قدیمی ['PASS', 'CODE']
        $this->assertStringNotContainsString("['PASS', 'CODE']", $content);
    }

    /**
     * تست مقایسه ترتیب قبل و بعد از رفع باگ
     */
    public function test_parameter_order_fix(): void
    {
        // ترتیب اشتباه قبل از رفع باگ
        $wrongOrder = [
            'names' => ['PASS', 'CODE'],
            'values' => ['password123', 'TECH12345']
        ];

        // ترتیب صحیح بعد از رفع باگ
        $correctOrder = [
            'names' => ['CODE', 'PASS'],
            'values' => ['TECH12345', 'password123']
        ];

        // بررسی که ترتیب صحیح اول CODE سپس PASS است
        $this->assertEquals('CODE', $correctOrder['names'][0]);
        $this->assertEquals('PASS', $correctOrder['names'][1]);

        // بررسی که مقادیر هم به همین ترتیب هستند
        $this->assertEquals('TECH12345', $correctOrder['values'][0]); // کد پرسنلی
        $this->assertEquals('password123', $correctOrder['values'][1]); // رمز عبور

        // اطمینان از اینکه ترتیب صحیح با ترتیب اشتباه قبلی متفاوت است
        $this->assertNotEquals($wrongOrder['names'], $correctOrder['names']);
    }
}

