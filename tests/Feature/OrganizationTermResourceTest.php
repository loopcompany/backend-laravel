<?php
namespace Tests\Feature;
use App\Models\{Admin,OrganizationTerm};
use App\Filament\Resources\{TermResource,WarrantyResource,OrganizationTermResource};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
class OrganizationTermResourceTest extends TestCase
{
    use RefreshDatabase;
    public function test_anonymous_actor_cannot_access_content_resources(): void
    {
        foreach ([TermResource::class,WarrantyResource::class,OrganizationTermResource::class] as $resource) {
            $this->assertFalse($resource::canViewAny());
            $this->assertFalse($resource::canCreate());
        }
    }
    public function test_content_view_permission_does_not_grant_mutations(): void
    {
        $admin=Admin::create(['name'=>'Editor','email'=>'editor@test.local','password'=>'password','staff_type'=>Admin::STAFF_OFFICE]);
        $this->actingAs($admin,'admin');
        foreach ([TermResource::class=>'terms',WarrantyResource::class=>'warranties',OrganizationTermResource::class=>'organization-terms'] as $resource=>$suffix) {
            foreach (['view','create','edit','delete'] as $action) Permission::findOrCreate("$action-$suffix",'admin');
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $admin->syncPermissions(["view-$suffix"]);
            $this->assertTrue($resource::canViewAny());
            $this->assertFalse($resource::canCreate());
            $this->assertFalse($resource::canEdit(new OrganizationTerm()));
            $this->assertFalse($resource::canDelete(new OrganizationTerm()));
            $admin->syncPermissions(["create-$suffix"]);
            $this->assertFalse($resource::canViewAny());
            $this->assertTrue($resource::canCreate());
            $admin->syncPermissions(["edit-$suffix"]);
            $this->assertTrue($resource::canEdit(new OrganizationTerm()));
            $this->assertFalse($resource::canDelete(new OrganizationTerm()));
            $admin->syncPermissions(["delete-$suffix"]);
            $this->assertTrue($resource::canDelete(new OrganizationTerm()));
        }
    }
    public function test_organization_terms_persist_and_can_be_updated(): void
    {
        $term=OrganizationTerm::create(['title'=>'قوانین سازمانی','description'=>'شرایط استفاده']);
        $this->assertSame('شرایط استفاده',$term->fresh()->description);
        $term->update(['description'=>'شرایط اصلاح‌شده']);
        $this->assertSame('شرایط اصلاح‌شده',$term->fresh()->description);
    }
}
