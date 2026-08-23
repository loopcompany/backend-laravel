<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Warranty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarrantyResourceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * تست دسترسی به API endpoint برای دریافت لیست warranties
     */
    public function test_can_get_warranties_list(): void
    {
        // ایجاد چند گارانتی تست
        Warranty::factory()->create([
            'title' => 'گارانتی اصالت کالا',
            'description' => 'تمامی کالاها دارای گارانتی اصالت هستند.'
        ]);

        Warranty::factory()->create([
            'title' => 'ضمانت بازگشت وجه',
            'description' => 'در صورت عدم رضایت، وجه شما بازگردانده می‌شود.'
        ]);

        // فراخوانی API
        $response = $this->getJson('/api/info/warranties');

        // بررسی پاسخ
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ])
            ->assertJsonCount(2, 'data');

        // بررسی محتوا
        $response->assertJsonFragment([
            'title' => 'گارانتی اصالت کالا'
        ])->assertJsonFragment([
            'title' => 'ضمانت بازگشت وجه'
        ]);
    }

    /**
     * تست ساختار WarrantyResource در Filament
     */
    public function test_warranty_resource_structure(): void
    {
        // بررسی وجود فایل WarrantyResource
        $resourceFile = app_path('Filament/Resources/WarrantyResource.php');
        $this->assertFileExists($resourceFile);

        // بررسی وجود Pages
        $this->assertFileExists(app_path('Filament/Resources/WarrantyResource/Pages/ListWarranties.php'));
        $this->assertFileExists(app_path('Filament/Resources/WarrantyResource/Pages/CreateWarranty.php'));
        $this->assertFileExists(app_path('Filament/Resources/WarrantyResource/Pages/EditWarranty.php'));

        // بررسی محتوای فایل
        $content = file_get_contents($resourceFile);
        
        // بررسی استفاده از مدل صحیح
        $this->assertStringContainsString('protected static ?string $model = Warranty::class;', $content);
        
        // بررسی navigation
        $this->assertStringContainsString("'مدیریت محتوا'", $content);
        $this->assertStringContainsString("'گارانتی و ضمانت'", $content);
        
        // بررسی permissions
        $this->assertStringContainsString("'manage-warranties'", $content);
    }

    /**
     * تست اضافه شدن permission به AdminRoleService
     */
    public function test_warranty_permission_exists_in_admin_role_service(): void
    {
        $serviceFile = app_path('Services/AdminRoleService.php');
        $this->assertFileExists($serviceFile);

        $content = file_get_contents($serviceFile);
        
        // بررسی وجود permission
        $this->assertStringContainsString("'manage-warranties'", $content);
        $this->assertStringContainsString("'مدیریت گارانتی و ضمانت'", $content);
    }
}
