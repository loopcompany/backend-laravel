<?php

namespace Tests\Feature;

use App\Filament\Resources;
use App\Models\Admin;
use App\Models\CooperationRequest;
use App\Services\AdminRoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\MakesPeople;
use Tests\TestCase;

/**
 * همه‌ی صفحه‌های جدید/تغییرکرده‌ی پنل برای مدیر کل بدون خطا باز می‌شوند.
 */
class NewAdminPagesSmokeTest extends TestCase
{
    use RefreshDatabase, MakesPeople;

    public function test_new_and_changed_admin_pages_render(): void
    {
        $service = app(AdminRoleService::class);
        $service->createBasicPermissions();
        $service->createBasicRoles();

        $admin = Admin::create(['name' => 'Root', 'email' => 'root@test.local', 'password' => 'password', 'staff_type' => Admin::STAFF_MANAGER]);
        $admin->forceFill(['is_active' => true])->save();
        $admin->assignRole(Role::where('name', 'super-admin')->where('guard_name', 'admin')->first());
        $this->actingAs($admin, 'admin');

        $technician = $this->makeTechnician();
        $orgUser = $this->makeUser(['account_type' => 'company']);
        \App\Models\Organization::factory()->create(['user_id' => $orgUser->id]);

        $urls = [
            Resources\AccountDeletionRequestResource::getUrl('index'),
            Resources\OrganizationDocumentResource::getUrl('index'),
            Resources\OrganizationUserResource::getUrl('index'),
            Resources\AppVersionResource::getUrl('index'),
            Resources\CooperationRequestResource::getUrl('index'),
            Resources\CooperationRequestResource::getUrl('create'),
            Resources\EmploymentFileResource::getUrl('index'),
            Resources\EmploymentFileResource::getUrl('create'),
            Resources\FaultReportResource::getUrl('index'),
            Resources\ReferralCodeResource::getUrl('index'),
            Resources\ReferralCodeResource::getUrl('create'),
            Resources\OrderResource::getUrl('index'),
            Resources\TechnicianResource::getUrl('edit', ['record' => $technician]),
            Resources\TechnicianResource::getUrl('create'),
            Resources\UserResource::getUrl('edit', ['record' => $orgUser]),
        ];

        foreach ($urls as $url) {
            $this->assertSame(200, $this->get($url)->status(), $url);
        }

        $this->get(Resources\FaultReportResource::getUrl('index'))->assertSee('عیوب سرویس / محصول');
        $this->get(Resources\TechnicianResource::getUrl('edit', ['record' => $technician]))
            ->assertSee('موجودی کیف پول تکنسین:')
            ->assertSee('درانتظار اعزام');
    }
}
