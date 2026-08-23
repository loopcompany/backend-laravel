<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Warranty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WarrantyPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // ایجاد permission و سمت‌ها
        Permission::create(['name' => 'access-admin-panel', 'guard_name' => 'admin']);
        Permission::create(['name' => 'manage-warranties', 'guard_name' => 'admin']);
        Permission::create(['name' => 'manage-terms', 'guard_name' => 'admin']);
        
        Role::create(['name' => 'super-admin', 'guard_name' => 'admin'])
            ->givePermissionTo(['access-admin-panel', 'manage-warranties', 'manage-terms']);
        
        Role::create(['name' => 'admin', 'guard_name' => 'admin'])
            ->givePermissionTo(['access-admin-panel', 'manage-warranties', 'manage-terms']);
        
        Role::create(['name' => 'moderator', 'guard_name' => 'admin'])
            ->givePermissionTo(['access-admin-panel', 'manage-warranties', 'manage-terms']);
        
        Role::create(['name' => 'viewer', 'guard_name' => 'admin'])
            ->givePermissionTo(['access-admin-panel']);
    }

    /**
     * تست وجود permission در AdminRoleService
     */
    public function test_warranty_permission_exists_in_admin_role_service(): void
    {
        $serviceFile = app_path('Services/AdminRoleService.php');
        $content = file_get_contents($serviceFile);
        
        // بررسی تعریف permission
        $this->assertStringContainsString("'manage-warranties' => 'مدیریت گارانتی و ضمانت'", $content);
        
        // بررسی وجود در سمت admin
        $this->assertStringContainsString("'manage-warranties'", $content);
    }

    /**
     * تست ساختار permissions در WarrantyResource
     */
    public function test_warranty_resource_has_correct_permission_structure(): void
    {
        $resourceFile = app_path('Filament/Resources/WarrantyResource.php');
        $content = file_get_contents($resourceFile);
        
        // بررسی متدهای permission
        $this->assertStringContainsString('protected static function getViewPermission(): string', $content);
        $this->assertStringContainsString('protected static function getCreatePermission(): string', $content);
        $this->assertStringContainsString('protected static function getEditPermission(): string', $content);
        $this->assertStringContainsString('protected static function getDeletePermission(): string', $content);
        
        // بررسی که همه متدها manage-warranties برمی‌گردانند
        $this->assertEquals(4, substr_count($content, "return 'manage-warranties';"));
    }

    /**
     * تست الگوی permission مشابه با TermResource
     */
    public function test_warranty_follows_same_pattern_as_term_resource(): void
    {
        $termResourceFile = app_path('Filament/Resources/TermResource.php');
        $warrantyResourceFile = app_path('Filament/Resources/WarrantyResource.php');
        
        $termContent = file_get_contents($termResourceFile);
        $warrantyContent = file_get_contents($warrantyResourceFile);
        
        // بررسی که هر دو از الگوی یکسان استفاده می‌کنند
        // Term: همه permissions برابر با 'manage-terms'
        $this->assertEquals(4, substr_count($termContent, "return 'manage-terms';"));
        
        // Warranty: همه permissions برابر با 'manage-warranties'
        $this->assertEquals(4, substr_count($warrantyContent, "return 'manage-warranties';"));
    }

    /**
     * تست دسترسی Super Admin
     */
    public function test_super_admin_has_warranty_permission(): void
    {
        $superAdmin = Admin::create([
            'name' => 'Super Admin',
            'email' => 'super@test.com',
            'password' => bcrypt('password'),
            'is_active' => true
        ]);
        $superAdmin->assignRole('super-admin');
        
        $this->assertTrue($superAdmin->hasPermissionTo('manage-warranties', 'admin'));
    }

    /**
     * تست دسترسی Admin
     */
    public function test_admin_has_warranty_permission(): void
    {
        $admin = Admin::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'is_active' => true
        ]);
        $admin->assignRole('admin');
        
        $this->assertTrue($admin->hasPermissionTo('manage-warranties', 'admin'));
    }

    /**
     * تست دسترسی Moderator
     */
    public function test_moderator_has_warranty_permission(): void
    {
        $moderator = Admin::create([
            'name' => 'Moderator',
            'email' => 'moderator@test.com',
            'password' => bcrypt('password'),
            'is_active' => true
        ]);
        $moderator->assignRole('moderator');
        
        $this->assertTrue($moderator->hasPermissionTo('manage-warranties', 'admin'));
    }

    /**
     * تست عدم دسترسی Viewer
     */
    public function test_viewer_does_not_have_warranty_permission(): void
    {
        $viewer = Admin::create([
            'name' => 'Viewer',
            'email' => 'viewer@test.com',
            'password' => bcrypt('password'),
            'is_active' => true
        ]);
        $viewer->assignRole('viewer');
        
        $this->assertFalse($viewer->hasPermissionTo('manage-warranties', 'admin'));
    }

    /**
     * تست مقایسه کامل permissions با TermResource
     */
    public function test_warranty_permissions_match_term_pattern(): void
    {
        $serviceFile = app_path('Services/AdminRoleService.php');
        $content = file_get_contents($serviceFile);
        
        // بررسی که manage-terms و manage-warranties در کنار هم تعریف شده‌اند
        $termsPos = strpos($content, "'manage-terms' => 'مدیریت قوانین و مقررات'");
        $privacyPos = strpos($content, "'manage-privacy' => 'مدیریت حریم خصوصی'");
        $warrantiesPos = strpos($content, "'manage-warranties' => 'مدیریت گارانتی و ضمانت'");
        
        $this->assertNotFalse($termsPos);
        $this->assertNotFalse($privacyPos);
        $this->assertNotFalse($warrantiesPos);
        
        // بررسی ترتیب: terms < privacy < warranties
        $this->assertLessThan($privacyPos, $termsPos);
        $this->assertLessThan($warrantiesPos, $privacyPos);
    }

    /**
     * تست وجود در سمت‌های مختلف
     */
    public function test_warranty_permission_in_all_relevant_roles(): void
    {
        $serviceFile = app_path('Services/AdminRoleService.php');
        $content = file_get_contents($serviceFile);
        
        // بررسی وجود در آرایه permissions سمت admin
        $adminRolePattern = "/'admin' => \[.*?'manage-warranties'.*?\]/s";
        $this->assertMatchesRegularExpression($adminRolePattern, $content);
        
        // بررسی وجود در آرایه permissions سمت moderator
        $moderatorRolePattern = "/'moderator' => \[.*?'manage-warranties'.*?\]/s";
        $this->assertMatchesRegularExpression($moderatorRolePattern, $content);
    }

    /**
     * تست consistency بین Resource‌های محتوا
     */
    public function test_content_resources_consistency(): void
    {
        $resources = [
            'Term' => ['permission' => 'manage-terms', 'label' => 'قوانین و مقررات'],
            'Privacy' => ['permission' => 'manage-privacy', 'label' => 'حریم خصوصی'],
            'Warranty' => ['permission' => 'manage-warranties', 'label' => 'گارانتی و ضمانت'],
        ];
        
        foreach ($resources as $resourceName => $data) {
            $resourceFile = app_path("Filament/Resources/{$resourceName}Resource.php");
            $this->assertFileExists($resourceFile);
            
            $content = file_get_contents($resourceFile);
            
            // بررسی navigation group
            $this->assertStringContainsString("'مدیریت محتوا'", $content);
            
            // بررسی تعداد permission methods
            $this->assertEquals(4, substr_count($content, "return '{$data['permission']}';"));
        }
    }
}
