<?php
namespace Tests\Feature;

use App\Models\{Admin,Order,OrganizationTerm};
use App\Filament\Resources\{RoleResource,PermissionResource};
use App\Services\{AdminDelegation,AdminPermissionCatalog};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\{Permission,Role};
use Tests\Concerns\MakesPeople;
use Tests\TestCase;

class FinalEmployerAnswersTest extends TestCase
{
    use RefreshDatabase, MakesPeople;
    private function closed(): array
    {
        $user=$this->makeUser();$tech=$this->makeTechnician();
        $order=Order::withoutEvents(fn()=>$this->makeOrder($user,['technician_id'=>$tech->id,'status'=>2,'finished_at'=>now(),'payment_status'=>1]));
        return [$user,$tech,$order];
    }
    public function test_customer_can_read_but_cannot_mutate_closed_order_and_phone_is_absent(): void
    {
        [$user,$tech,$order]=$this->closed();Sanctum::actingAs($user);
        $this->getJson('/api/orders/'.$order->id)->assertOk()->assertDontSee($tech->phone);
        $this->postJson('/api/orders/detail',['orderId'=>$order->id])->assertOk()->assertDontSee($tech->phone);
        foreach (['final-description','in-place-description','visit/decision','quote/decision','return-followup'] as $action) {
            $this->postJson('/api/orders/'.$order->id.'/'.$action,[])->assertStatus(409)->assertJsonPath('error_code','ORDER_CLOSED_READ_ONLY');
        }
        $this->postJson('/api/orders/cancel',['orderId'=>$order->id])->assertStatus(409);
        $this->assertSame(2,(int)$order->fresh()->status);
    }
    public function test_technician_cannot_change_closed_assignment_or_report(): void
    {
        [$user,$tech,$order]=$this->closed();Sanctum::actingAs($tech);
        foreach (['set-off','arrive','assignment','visit-outcome','requote'] as $action) $this->postJson('/api/technician/orders/'.$order->id.'/'.$action,[])->assertStatus(409);
        $this->postJson('/api/technician/order-reports',['order_id'=>$order->id])->assertStatus(409);
    }
    public function test_feedback_can_be_dismissed_without_erasing_order_and_can_still_be_submitted(): void
    {
        [$user,$tech,$order]=$this->closed();Sanctum::actingAs($user);
        $this->postJson('/api/reviews/dismiss',['order_id'=>$order->id])->assertOk();
        $this->assertNotNull($order->fresh()->review_dismissed_at);
        $this->assertSame(2,(int)$order->fresh()->status);
        $this->postJson('/api/reviews',['order_id'=>$order->id,'technician_id'=>$tech->id,'application_rate'=>5,'technician_rate'=>4,'support_rate'=>5,'description'=>'نظر اختیاری کاربر'])->assertCreated();
    }
    public function test_other_accounts_cannot_dismiss_feedback(): void
    {
        [$user,$tech,$order]=$this->closed();Sanctum::actingAs($this->makeUser());
        $this->postJson('/api/reviews/dismiss',['order_id'=>$order->id])->assertNotFound();
        $this->assertNull($order->fresh()->review_dismissed_at);
    }
    public function test_model_guard_also_blocks_a_closed_order_update(): void
    {
        [$user,$tech,$order]=$this->closed();Sanctum::actingAs($tech);
        $this->expectException(ValidationException::class);
        $order->update(['technician_des'=>'ویرایش غیرمجاز']);
    }
    private function admin(): Admin
    {
        $admin=Admin::create(['name'=>'Staff','email'=>uniqid().'@test.local','password'=>'password','staff_type'=>Admin::STAFF_OFFICE]);
        $this->actingAs($admin,'admin');return $admin;
    }
    public function test_normal_admin_has_no_implicit_permission_management_authority(): void
    {
        $actor=$this->admin();
        $this->assertFalse(RoleResource::canViewAny());$this->assertFalse(RoleResource::canCreate());
        $this->assertFalse(PermissionResource::canCreate());$this->assertSame([],AdminDelegation::permissionIds());
        $actor->givePermissionTo('create-roles');$this->assertTrue(RoleResource::canCreate());
        $this->assertFalse(AdminDelegation::can('assign-permissions'));
        $actor->givePermissionTo('create-permissions');$this->assertTrue(PermissionResource::canCreate());
    }
    public function test_delegation_cannot_grant_unheld_permissions_or_super_admin_role(): void
    {
        $actor=$this->admin();$actor->givePermissionTo(['assign-roles','assign-permissions','view-dashboard']);
        $low=Role::findOrCreate('limited','admin');$low->givePermissionTo('view-dashboard');
        $high=Role::findOrCreate('privileged','admin');$high->givePermissionTo('delete-user');
        $root=Role::findOrCreate('super-admin','admin');
        $this->assertContains($low->id,AdminDelegation::roleIds());
        $this->assertNotContains($high->id,AdminDelegation::roleIds());
        $this->assertNotContains($root->id,AdminDelegation::roleIds());
        $this->assertNotContains(Permission::findByName('delete-user','admin')->id,AdminDelegation::permissionIds());
        $this->assertFalse(RoleResource::canEdit($root));
    }
    public function test_permission_inventory_covers_all_resource_bindings(): void
    {
        AdminPermissionCatalog::sync();
        foreach (AdminPermissionCatalog::definitions() as $name=>$label) $this->assertDatabaseHas('permissions',['name'=>$name,'guard_name'=>'admin']);
        $this->assertGreaterThan(100,count(AdminPermissionCatalog::definitions()));
    }
    public function test_role_creation_renders_even_with_unlabelled_legacy_permission(): void
    {
        $actor=$this->admin();$actor->forceFill(['is_active'=>true])->save();$role=Role::findOrCreate('super-admin','admin');$actor->assignRole($role);
        Permission::findOrCreate('old-custom-capability','admin')->update(['fa_name'=>null]);
        AdminPermissionCatalog::sync();
        $actor->givePermissionTo('access-admin-panel');
        $this->get('https://clpiran.com/admin/roles/create')->assertOk()->assertSee('old-custom-capability');
    }
    public function test_admin_editor_cannot_reset_a_super_admin_or_broader_accounts_password(): void
    {
        $actor=$this->admin();$actor->givePermissionTo('manage-admins');
        $target=Admin::create(['name'=>'Root','email'=>'root@test.local','password'=>'password','staff_type'=>Admin::STAFF_OFFICE]);
        $target->assignRole(Role::findOrCreate('super-admin','admin'));
        $this->assertFalse(\App\Filament\Resources\AdminResource::canEdit($target));
        $this->assertFalse(\App\Filament\Resources\AdminResource::canDelete($target));
        $target->removeRole('super-admin');$target->givePermissionTo('assign-permissions');
        $this->assertFalse(\App\Filament\Resources\AdminResource::canEdit($target));
        $target->syncPermissions([]);
        $this->assertTrue(\App\Filament\Resources\AdminResource::canEdit($target));
        $this->assertFalse(\App\Filament\Resources\AdminResource::canDeleteAny());
    }
}
