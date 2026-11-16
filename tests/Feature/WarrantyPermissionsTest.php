<?php
namespace Tests\Feature;
use App\Models\{Admin,Warranty};
use App\Filament\Resources\WarrantyResource;
use App\Services\AdminPermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
class WarrantyPermissionsTest extends TestCase
{
    use RefreshDatabase;
    private function actor(): Admin {
        $admin=Admin::create(['name'=>'Editor','email'=>'warranty@test.local','password'=>'password','staff_type'=>Admin::STAFF_OFFICE]);
        $this->actingAs($admin,'admin');return $admin;
    }
    public function test_view_authority_does_not_grant_create_edit_or_delete(): void {
        $admin=$this->actor();$admin->givePermissionTo('view-warranties');
        $this->assertTrue(WarrantyResource::canViewAny());
        $this->assertFalse(WarrantyResource::canCreate());
        $this->assertFalse(WarrantyResource::canEdit(new Warranty()));
        $this->assertFalse(WarrantyResource::canDelete(new Warranty()));
    }
    public function test_mutation_permissions_are_independently_granted(): void {
        $admin=$this->actor();
        $admin->syncPermissions(['create-warranties']);$this->assertTrue(WarrantyResource::canCreate());$this->assertFalse(WarrantyResource::canEdit(new Warranty()));
        $admin->syncPermissions(['edit-warranties']);$this->assertFalse(WarrantyResource::canCreate());$this->assertTrue(WarrantyResource::canEdit(new Warranty()));$this->assertFalse(WarrantyResource::canDelete(new Warranty()));
        $admin->syncPermissions(['delete-warranties']);$this->assertTrue(WarrantyResource::canDelete(new Warranty()));$this->assertFalse(WarrantyResource::canEdit(new Warranty()));
    }
    public function test_catalog_update_does_not_grant_regular_admins_new_authority(): void {
        $admin=$this->actor();AdminPermissionCatalog::sync();
        $this->assertFalse(WarrantyResource::canViewAny());$this->assertFalse(WarrantyResource::canCreate());
    }
    public function test_existing_super_admin_receives_complete_catalog(): void {
        $admin=$this->actor();$role=Role::findOrCreate('super-admin','admin');$admin->assignRole($role);AdminPermissionCatalog::sync();$admin->unsetRelation('roles');
        foreach (['view','create','edit','delete'] as $action) $this->assertTrue($admin->hasPermissionTo($action.'-warranties','admin'));
    }
}
