<?php

namespace Tests\Feature;

use Tests\TestCase;

class OrganizationTermResourceTest extends TestCase
{
    /**
     * تست ساختار OrganizationTermResource
     */
    public function test_organization_term_resource_structure(): void
    {
        // بررسی وجود فایل‌ها
        $this->assertFileExists(app_path('Models/OrganizationTerm.php'));
        $this->assertFileExists(app_path('Filament/Resources/OrganizationTermResource.php'));
        $this->assertFileExists(app_path('Filament/Resources/OrganizationTermResource/Pages/ListOrganizationTerms.php'));
        $this->assertFileExists(app_path('Filament/Resources/OrganizationTermResource/Pages/CreateOrganizationTerm.php'));
        $this->assertFileExists(app_path('Filament/Resources/OrganizationTermResource/Pages/EditOrganizationTerm.php'));
        $this->assertFileExists(database_path('factories/OrganizationTermFactory.php'));

        // بررسی میگریشن
        $migrations = glob(database_path('migrations/*_create_organization_terms_table.php'));
        $this->assertCount(1, $migrations, 'باید یک میگریشن organization_terms وجود داشته باشد');
    }

    /**
     * تست محتوای OrganizationTermResource
     */
    public function test_organization_term_resource_content(): void
    {
        $resourceFile = app_path('Filament/Resources/OrganizationTermResource.php');
        $content = file_get_contents($resourceFile);
        
        // بررسی مدل
        $this->assertStringContainsString('protected static ?string $model = OrganizationTerm::class;', $content);
        
        // بررسی navigation
        $this->assertStringContainsString("'مدیریت محتوا'", $content);
        $this->assertStringContainsString("'قوانین و مقررات سازمانی'", $content);
        
        // بررسی permissions
        $this->assertStringContainsString("'manage-organization-terms'", $content);
        $this->assertEquals(4, substr_count($content, "return 'manage-organization-terms';"));
    }

    /**
     * تست محتوای مدل
     */
    public function test_organization_term_model_structure(): void
    {
        $modelFile = app_path('Models/OrganizationTerm.php');
        $content = file_get_contents($modelFile);
        
        // بررسی fillable
        $this->assertStringContainsString("'title'", $content);
        $this->assertStringContainsString("'description'", $content);
        
        // بررسی HasFactory
        $this->assertStringContainsString('use HasFactory;', $content);
    }

    /**
     * تست permission در AdminRoleService
     */
    public function test_organization_term_permission_in_admin_role_service(): void
    {
        $serviceFile = app_path('Services/AdminRoleService.php');
        $content = file_get_contents($serviceFile);
        
        // بررسی تعریف permission
        $this->assertStringContainsString("'manage-organization-terms' => 'مدیریت قوانین و مقررات سازمانی'", $content);
        
        // بررسی وجود در نقش admin
        $this->assertStringContainsString("'manage-organization-terms'", $content);
    }

    /**
     * تست الگوی مشابه با TermResource
     */
    public function test_follows_term_resource_pattern(): void
    {
        $termResourceFile = app_path('Filament/Resources/TermResource.php');
        $organizationTermResourceFile = app_path('Filament/Resources/OrganizationTermResource.php');
        
        $termContent = file_get_contents($termResourceFile);
        $organizationTermContent = file_get_contents($organizationTermResourceFile);
        
        // بررسی الگوی permissions (همه برابر با یک permission)
        $this->assertEquals(4, substr_count($termContent, "return 'manage-terms';"));
        $this->assertEquals(4, substr_count($organizationTermContent, "return 'manage-organization-terms';"));
        
        // بررسی فیلدهای فرم
        $this->assertStringContainsString('Forms\Components\TextInput::make(\'title\')', $termContent);
        $this->assertStringContainsString('Forms\Components\TextInput::make(\'title\')', $organizationTermContent);
        
        $this->assertStringContainsString('Forms\Components\RichEditor::make(\'description\')', $termContent);
        $this->assertStringContainsString('Forms\Components\RichEditor::make(\'description\')', $organizationTermContent);
    }

    /**
     * تست میگریشن
     */
    public function test_migration_structure(): void
    {
        $migrations = glob(database_path('migrations/*_create_organization_terms_table.php'));
        $this->assertCount(1, $migrations);
        
        $migrationContent = file_get_contents($migrations[0]);
        
        // بررسی ستون‌ها
        $this->assertStringContainsString('$table->id();', $migrationContent);
        $this->assertStringContainsString('$table->string(\'title\');', $migrationContent);
        $this->assertStringContainsString('$table->text(\'description\');', $migrationContent);
        $this->assertStringContainsString('$table->timestamps();', $migrationContent);
    }

    /**
     * تست consistency بین Resource‌های محتوا
     */
    public function test_content_management_resources_consistency(): void
    {
        $resources = [
            'Term' => ['permission' => 'manage-terms', 'sort' => 10],
            'Warranty' => ['permission' => 'manage-warranties', 'sort' => 11],
            'OrganizationTerm' => ['permission' => 'manage-organization-terms', 'sort' => 12],
        ];
        
        foreach ($resources as $resourceName => $data) {
            $resourceFile = app_path("Filament/Resources/{$resourceName}Resource.php");
            $this->assertFileExists($resourceFile);
            
            $content = file_get_contents($resourceFile);
            
            // همه در گروه مدیریت محتوا
            $this->assertStringContainsString("'مدیریت محتوا'", $content);
            
            // هر کدام permission مخصوص خودشون رو دارن
            $this->assertStringContainsString("return '{$data['permission']}';", $content);
        }
    }
}
