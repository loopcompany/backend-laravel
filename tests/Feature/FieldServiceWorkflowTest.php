<?php

namespace Tests\Feature;

use App\Filament\Resources\OrderResource;
use App\Filament\Resources\OrderResource\Pages\ViewOrder;
use App\Filament\Resources\OrderResource\RelationManagers\FieldServiceVisitsRelationManager;
use App\Models\Admin;
use App\Models\FieldServiceVisit;
use App\Models\Order;
use App\Models\Organization;
use App\Models\Technician;
use App\Models\User;
use App\Services\AdminRoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\Concerns\MakesPeople;
use Tests\TestCase;

class FieldServiceWorkflowTest extends TestCase
{
    use RefreshDatabase, MakesPeople;

    private function context(): array
    {
        $organization = $this->makeUser(['account_type' => 'company']);
        Organization::factory()->create(['user_id' => $organization->id, 'organization_name' => 'شرکت آزمایشی']);
        $technician = $this->makeTechnician(['technician_type' => Technician::TYPE_FIELD]);
        $order = Order::withoutEvents(fn () => $this->makeOrder($organization, [
            'technician_id' => $technician->id, 'status' => 1,
        ]));
        $order->user_address()->update(['room' => '۳۰۵']);

        return [$organization, $technician, $order];
    }

    private function report(array $overrides = []): array
    {
        return array_merge([
            'site_condition' => 'problem',
            'problem_description' => 'دستگاه روشن نمی‌شود',
            'actions' => ['بررسی کابل', 'تعویض باتری'],
            'has_remaining_work' => false,
            'outcome' => 'completed',
        ], $overrides);
    }

    public function test_organization_approval_closes_only_completed_work(): void
    {
        [$organization, $technician, $order] = $this->context();
        Sanctum::actingAs($technician);

        $this->postJson("/api/technician/orders/{$order->id}/field-service/start", [
            'latitude' => 35.7001, 'longitude' => 51.4012,
        ])->assertCreated()->assertJsonPath('data.status', 'in_progress');
        $visit = FieldServiceVisit::firstOrFail();

        $this->postJson("/api/technician/orders/{$order->id}/field-service/start")
            ->assertStatus(409);
        $this->putJson("/api/technician/orders/{$order->id}/field-service/{$visit->id}", $this->report())
            ->assertOk()->assertJsonPath('data.status', 'submitted');
        $this->assertEquals(1, $order->fresh()->status);

        Sanctum::actingAs($organization);
        $this->getJson("/api/organization/orders/{$order->id}/field-service")
            ->assertOk()
            ->assertJsonPath('data.work_status', 'awaiting_approval')
            ->assertJsonPath('data.order.location.room', '۳۰۵')
            ->assertJsonPath('data.visits.0.actions.1', 'تعویض باتری');
        $this->postJson("/api/organization/orders/{$order->id}/field-service/{$visit->id}/review", [
            'decision' => 'approve',
        ])->assertOk()->assertJsonPath('data.work_status', 'completed');

        $this->assertEquals(2, $order->fresh()->status);
        $this->assertEquals('approved', $visit->fresh()->status);
        $this->assertDatabaseHas('field_service_reviews', [
            'field_service_visit_id' => $visit->id, 'user_id' => $organization->id, 'decision' => 'approve',
        ]);
    }

    public function test_correction_history_and_follow_up_do_not_close_order(): void
    {
        [$organization, $technician, $order] = $this->context();
        Sanctum::actingAs($technician);
        $visitId = $this->postJson("/api/technician/orders/{$order->id}/field-service/start")
            ->assertCreated()->json('data.visit_id');
        $pending = $this->report([
            'has_remaining_work' => true,
            'remaining_action' => 'تست نهایی',
            'continuation_owner' => 'next_technician',
            'continuation_note' => 'پس از رسیدن قطعه',
            'outcome' => 'waiting_for_part',
        ]);

        $this->putJson("/api/technician/orders/{$order->id}/field-service/{$visitId}", $pending)
            ->assertOk();
        Sanctum::actingAs($organization);
        $this->postJson("/api/organization/orders/{$order->id}/field-service/{$visitId}/review", [
            'decision' => 'request_changes', 'note' => 'عکس قطعه را اضافه کنید',
        ])->assertOk()->assertJsonPath('data.work_status', 'changes_requested');

        Sanctum::actingAs($technician);
        $this->putJson("/api/technician/orders/{$order->id}/field-service/{$visitId}", $pending)
            ->assertOk();
        Sanctum::actingAs($organization);
        $this->postJson("/api/organization/orders/{$order->id}/field-service/{$visitId}/review", [
            'decision' => 'approve',
        ])->assertOk()->assertJsonPath('data.work_status', 'follow_up_required');

        $this->assertEquals(1, $order->fresh()->status);
        $this->assertCount(2, FieldServiceVisit::findOrFail($visitId)->reviews);
        Sanctum::actingAs($technician);
        $this->postJson("/api/technician/orders/{$order->id}/field-service/start")
            ->assertCreated();
    }

    public function test_other_accounts_cannot_read_or_change_report_and_photo_requires_signature(): void
    {
        [$organization, $technician, $order] = $this->context();
        $otherTechnician = $this->makeTechnician();
        $otherOrganization = $this->makeUser(['account_type' => 'company']);

        Sanctum::actingAs($otherTechnician);
        $this->postJson("/api/technician/orders/{$order->id}/field-service/start")->assertForbidden();
        Sanctum::actingAs($otherOrganization);
        $this->getJson("/api/organization/orders/{$order->id}/field-service")->assertForbidden();

        Sanctum::actingAs($technician);
        $visitId = $this->postJson("/api/technician/orders/{$order->id}/field-service/start")
            ->assertCreated()->json('data.visit_id');
        Storage::fake('local');
        $uploaded = $this->post("/api/technician/orders/{$order->id}/field-service/{$visitId}/photos", [
            'kind' => 'before', 'photo' => UploadedFile::fake()->image('before.png'),
        ], ['Accept' => 'application/json'])->assertCreated();
        $photoId = $uploaded->json('data.id');
        $this->get("/api/field-service/photos/{$photoId}")->assertForbidden();
        $this->get($uploaded->json('data.url'))->assertOk();
    }

    public function test_invalid_report_cannot_be_submitted(): void
    {
        [$organization, $technician, $order] = $this->context();
        Sanctum::actingAs($technician);
        $visitId = $this->postJson("/api/technician/orders/{$order->id}/field-service/start")
            ->assertCreated()->json('data.visit_id');

        $this->putJson("/api/technician/orders/{$order->id}/field-service/{$visitId}", $this->report([
            'has_remaining_work' => true,
        ]))->assertUnprocessable();
        $this->assertEquals('in_progress', FieldServiceVisit::findOrFail($visitId)->status);
    }

    public function test_admin_order_page_shows_daily_reports_section(): void
    {
        [$organization, $technician, $order] = $this->context();
        app(AdminRoleService::class)->createBasicPermissions();
        app(AdminRoleService::class)->createBasicRoles();
        $admin = Admin::create([
            'name' => 'Admin', 'email' => 'admin@local.test', 'password' => 'secret123',
            'staff_type' => Admin::STAFF_MANAGER, 'is_active' => true,
        ]);
        $admin->assignRole(Role::where('name', 'super-admin')->where('guard_name', 'admin')->first());
        $this->actingAs($admin, 'admin');

        $this->get(OrderResource::getUrl('view', ['record' => $order]))
            ->assertOk()
            ->assertSee('گزارش‌های روزانهٔ تکنسین میدانی');

        $visit = $order->fieldServiceVisits()->create([
            'technician_id' => $technician->id,
            'started_at' => now()->subHour(),
            'finished_at' => now(),
            'status' => FieldServiceVisit::APPROVED,
            'actions' => ['بررسی دستگاه'],
            'has_remaining_work' => false,
            'outcome' => 'completed',
        ]);
        Livewire::test(FieldServiceVisitsRelationManager::class, [
            'ownerRecord' => $order,
            'pageClass' => ViewOrder::class,
        ])->mountTableAction('view', $visit)
            ->assertSee('بررسی دستگاه');
    }
}
