<?php

namespace Tests\Feature;

use App\Filament\Resources\AdminResource\Pages\ListAdmins;
use App\Filament\Resources\TechnicianResource\Pages\ListTechnicians;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\Admin;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * تفکیک و سرچ دسته‌بندی‌شده‌ی کاربران / تکنسین‌ها / کارکنان و مدیران.
 */
class PeopleCategoryFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function authAsSuperAdmin(): Admin
    {
        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'admin']);
        foreach (['view-user', 'create-user', 'edit-user', 'delete-user', 'manage-admins', 'view-technicians'] as $p) {
            $perm = Permission::firstOrCreate(['name' => $p, 'guard_name' => 'admin']);
            $role->givePermissionTo($perm);
        }

        $admin = Admin::create([
            'name' => 'Root', 'email' => 'root@test.local', 'password' => 'password',
            'staff_type' => Admin::STAFF_MANAGER,
        ]);
        $admin->assignRole($role);

        $this->actingAs($admin, 'admin');

        return $admin;
    }

    private function makeUser(string $name, string $accountType, bool $special): User
    {
        return User::create([
            'name' => $name,
            'phone' => 'p' . uniqid(),
            'code' => 'c' . uniqid(),
            'account_type' => $accountType,
            'is_special' => $special,
        ]);
    }

    public function test_user_category_scope_splits_all_six_categories(): void
    {
        $indN = $this->makeUser('ind-n', 'individual', false);
        $indS = $this->makeUser('ind-s', 'individual', true);
        $orgN = $this->makeUser('org-n', 'organization', false);
        $orgS = $this->makeUser('org-s', 'organization', true);
        $coN = $this->makeUser('co-n', 'company', false);
        $coS = $this->makeUser('co-s', 'company', true);

        $this->assertEquals([$indN->id], User::inCategories('individual_normal')->pluck('id')->all());
        $this->assertEquals([$indS->id], User::inCategories('individual_special')->pluck('id')->all());
        $this->assertEquals([$orgN->id], User::inCategories('organization_normal')->pluck('id')->all());
        $this->assertEquals([$orgS->id], User::inCategories('organization_special')->pluck('id')->all());
        $this->assertEquals([$coN->id], User::inCategories('company_normal')->pluck('id')->all());
        $this->assertEquals([$coS->id], User::inCategories('company_special')->pluck('id')->all());

        // گروهی
        $group = User::inCategories(['organization_normal', 'company_special'])->pluck('id')->sort()->values()->all();
        $this->assertEquals(collect([$orgN->id, $coS->id])->sort()->values()->all(), $group);
    }

    public function test_user_table_category_filter_and_search(): void
    {
        $this->authAsSuperAdmin();
        $target = $this->makeUser('Special Person', 'company', true);
        $this->makeUser('Normal Person', 'company', false);

        Livewire::test(ListUsers::class)
            ->filterTable('category', ['company_special'])
            ->assertCanSeeTableRecords([$target])
            ->assertCountTableRecords(1);

        Livewire::test(ListUsers::class)
            ->searchTable($target->code)
            ->assertCanSeeTableRecords([$target])
            ->assertCountTableRecords(1);
    }

    public function test_technician_type_scope_and_filter(): void
    {
        $this->authAsSuperAdmin();

        $field = Technician::create($this->technicianData('field', 'تکنسین میدانی', 'TR-1'));
        $legacy = Technician::create($this->technicianData('legacy', 'تکنسین جامع میدانی', 'TR-2'));
        $internal = Technician::create($this->technicianData('internal', 'تکنسین داخلی', 'TR-3'));

        $this->assertEqualsCanonicalizing(
            [$field->id, $legacy->id],
            Technician::ofTypes(Technician::TYPE_FIELD)->pluck('id')->all()
        );
        $this->assertEquals([$internal->id], Technician::ofTypes(Technician::TYPE_INTERNAL)->pluck('id')->all());

        Livewire::test(ListTechnicians::class)
            ->filterTable('technician_type', ['تکنسین میدانی'])
            ->assertCanSeeTableRecords([$field, $legacy])
            ->assertCanNotSeeTableRecords([$internal]);
    }

    public function test_admin_staff_type_scope_and_filter(): void
    {
        $this->authAsSuperAdmin();

        $office = Admin::create(['name' => 'off', 'email' => 'off@test.local', 'password' => 'x', 'personnel_code' => 'A-1', 'phone' => '0910', 'staff_type' => Admin::STAFF_OFFICE]);
        $fieldStaff = Admin::create(['name' => 'fld', 'email' => 'fld@test.local', 'password' => 'x', 'personnel_code' => 'A-2', 'phone' => '0911', 'staff_type' => Admin::STAFF_FIELD]);

        $this->assertEquals([$office->id], Admin::staffType(Admin::STAFF_OFFICE)->pluck('id')->all());

        Livewire::test(ListAdmins::class)
            ->filterTable('staff_type', ['میدانی'])
            ->assertCanSeeTableRecords([$fieldStaff])
            ->assertCanNotSeeTableRecords([$office]);

        Livewire::test(ListAdmins::class)
            ->searchTable('A-2')
            ->assertCanSeeTableRecords([$fieldStaff])
            ->assertCountTableRecords(1);
    }

    private function technicianData(string $name, string $type, string $code): array
    {
        return [
            'name' => $name, 'technician_type' => $type, 'referral_code' => $code,
            'phone' => 'tp' . uniqid(), 'father_name' => 'x', 'issued_from' => 'x', 'serial_number' => 'x',
            'marital_status' => 'مجرد', 'military_status' => 'معاف', 'education_status' => 'x',
            'telephone' => 'x', 'licence_date' => '2020-01-01', 'vehicle_type' => 'خودرو',
            'home_postal_code' => 'x', 'region' => 'x', 'city' => 'x', 'home_address' => 'x',
            'idea' => 'x', 'software_skill' => 'x', 'hardware_skill' => 'x',
            'software_weakness' => 'x', 'hardware_weakness' => 'x', 'commission' => 80, 'wallet' => 0,
            'approval_status' => 'approved', 'has_access' => true,
        ];
    }
}
