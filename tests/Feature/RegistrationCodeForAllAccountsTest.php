<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\City;
use App\Models\Province;
use App\Models\Region;
use App\Models\Technician;
use App\Models\User;
use App\Services\RegistrationCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\MakesPeople;
use Tests\TestCase;

/*
 * Employer request of 2026-11-23 (item 18): «شماره‌ی کاربری فقط رقم باشد، بدون خط تیره و نقطه» and
 * «همه‌ی کاربرها، چه تکنسین و چه کاربر و چه ادمین، باید این کد را داشته باشند».
 */
class RegistrationCodeForAllAccountsTest extends TestCase
{
    use RefreshDatabase, MakesPeople;

    private const MIGRATION = 'migrations/2026_11_24_090000_ensure_formula_codes_for_all_accounts.php';

    /** تهران با کد ۰۲۱ و شهرِ «تهران» و منطقه‌ی ۱۸ — همان نمونه‌ی کارفرما: 21 | 18 | … */
    private function tehran(): array
    {
        $province = Province::create(['title' => 'تهران', 'code' => '021']);
        $city = City::create(['title' => 'تهران', 'province_id' => $province->id]);
        $region = Region::create(['code' => '18', 'title' => 'منطقه ۱۸', 'city_id' => $city->id]);
        return ['province_id' => $province->id, 'city_id' => $city->id, 'region_id' => $region->id];
    }

    private function migration(): object
    {
        return require database_path(self::MIGRATION);
    }

    private function admin(string $code, string $staffType = Admin::STAFF_MANAGER): Admin
    {
        return Admin::create([
            'name' => 'مدیر', 'email' => 'admin-' . $code . '@test.local', 'personnel_code' => $code,
            'staff_type' => $staffType, 'password' => Hash::make('secret123'), 'is_active' => true,
        ]);
    }

    private function technician(array $attributes): Technician
    {
        $technician = $this->makeTechnician(['referral_code' => null]);
        $technician->forceFill($attributes + ['registration_method' => 1, 'technician_type' => 'تکنسین میدانی', 'city' => 'تهران', 'region' => '۱۸', 'approval_status' => Technician::APPROVAL_APPROVED]);
        $technician->save();
        return $technician->fresh();
    }

    private function everyCode(): array
    {
        return [
            ...User::pluck('code')->all(),
            ...Technician::pluck('referral_code')->all(),
            ...Admin::pluck('personnel_code')->all(),
        ];
    }

    public function test_codes_issued_in_the_dashed_window_lose_their_separators_without_using_new_numbers(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 24));
        $dashedUser = $this->makeUser(['code' => '21-18-05-60-3-0.000.007', 'registration_method' => 3]);
        $dashedTechnician = $this->technician(['referral_code' => '21-18-05-41-1-0.000.008']);
        $dashedAdmin = $this->admin('21-18-05-10-4-0.000.009');
        $plain = $this->makeUser(['code' => '2118056030000011', 'registration_method' => 3]);
        $this->assertTrue(RegistrationCodeService::isIssued('21-18-05-60-3-0.000.007'), 'the generator of that window produced such codes');

        $this->migration()->up();

        $this->assertSame('2118056030000007', $dashedUser->fresh()->code);
        $this->assertSame('2118054110000008', $dashedTechnician->fresh()->referral_code);
        $this->assertSame('2118051040000009', $dashedAdmin->fresh()->personnel_code);
        $this->assertSame('2118056030000011', $plain->fresh()->code);
        $this->assertDatabaseHas('registration_code_format_changes', ['table_name' => 'users', 'record_id' => $dashedUser->id, 'old_value' => '21-18-05-60-3-0.000.007', 'new_value' => '2118056030000007', 'reason' => 'separators']);
        $this->assertDatabaseHas('registration_code_sequence', ['id' => 1, 'last_number' => 0]);
    }

    public function test_every_account_gets_the_formula_code_and_the_old_value_is_recorded(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 24));
        $location = $this->tehran();
        $legacy = User::withoutEvents(fn () => $this->makeUser(['code' => '211805083', 'registration_method' => 3] + $location));                    // 9-digit code from before the formula
        $empty = User::withoutEvents(fn () => $this->makeUser(['code' => null, 'registration_method' => 3] + $location));
        $noLocation = $this->makeUser(['code' => '211805099']);                            // no region → cannot be issued yet
        $approved = $this->technician(['referral_code' => '211852025']);                   // approved, still on the old code
        $officeTechnician = $this->technician(['referral_code' => null, 'technician_type' => 'تکنسین داخلی']);
        $pending = $this->technician(['referral_code' => null, 'approval_status' => Technician::APPROVAL_PENDING]);
        $manager = $this->admin('a1b2c3d4e');
        $accountant = $this->admin('z9y8x7w6v', Admin::STAFF_OFFICE);


        foreach ([$manager, $accountant] as $admin) $admin->update(['registration_city_code' => '21', 'registration_region_code' => '18', 'registration_method' => 4]);
        $this->migration()->up();

        // The employer's example: 21 | 18 | 05 | <type> | <method> | 0000001 — digits only.
        $this->assertSame('2118056030000001', $legacy->fresh()->code);
        $this->assertSame(3, $legacy->fresh()->registration_method, 'a legacy self-registered account is treated as a website registration');
        $this->assertSame('2118056030000002', $empty->fresh()->code);
        $this->assertSame('211805099', $noLocation->fresh()->code, 'no location → nothing is invented');
        $this->assertSame('2118054110000003', $approved->fresh()->referral_code, 'field technician (41), registered in the app (1)');
        $this->assertSame('2118054010000004', $officeTechnician->fresh()->referral_code, 'office technician (40)');
        $this->assertNull($pending->fresh()->referral_code, 'a pending technician gets the code at approval time');
        $this->assertSame('2118051040000005', $manager->fresh()->personnel_code, 'manager (10), created by an admin (4)');
        $this->assertSame('2118053040000006', $accountant->fresh()->personnel_code, 'office staff (30)');

        $codes = array_values(array_filter($this->everyCode()));
        $this->assertCount(7, $codes);
        $this->assertSame($codes, array_unique($codes), 'codes are unique across users, technicians and admins');
        foreach (array_diff($codes, ['211805099']) as $code) {
            $this->assertMatchesRegularExpression('/^[0-9]{16}$/D', $code);
            $this->assertTrue(RegistrationCodeService::isIssued($code));
        }
        $this->assertDatabaseHas('registration_code_format_changes', ['table_name' => 'users', 'record_id' => $legacy->id, 'old_value' => '211805083', 'new_value' => '2118056030000001', 'reason' => 'formula']);
        $this->assertDatabaseHas('registration_code_format_changes', ['table_name' => 'admins', 'record_id' => $manager->id, 'old_value' => 'a1b2c3d4e', 'new_value' => '2118051040000005', 'reason' => 'formula']);
        $this->assertSame(6, (int) DB::table('registration_code_sequence')->where('id', 1)->value('last_number'));
    }

    public function test_admins_stay_as_they_are_when_no_default_location_is_configured(): void
    {
        $manager = $this->admin('a1b2c3d4e');
        $this->migration()->up();
        $this->assertSame('a1b2c3d4e', $manager->fresh()->personnel_code);
        $this->assertSame(0, DB::table('registration_code_format_changes')->where('reason', 'formula')->count());
    }

    public function test_running_twice_changes_nothing_and_down_restores_every_previous_value(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 24));
        $location = $this->tehran();
        $legacy = User::withoutEvents(fn () => $this->makeUser(['code' => '211805083', 'registration_method' => 3] + $location));
        $empty = User::withoutEvents(fn () => $this->makeUser(['code' => null, 'registration_method' => 3] + $location));
        $dashed = $this->makeUser(['code' => '21-18-05-60-3-0.000.007', 'registration_method' => 3]);
        $approved = $this->technician(['referral_code' => '211852025']);
        $referrer = $this->technician(['referral_code' => '2118056010000090', 'other_referral_code' => '211852025']);

        $manager = $this->admin('a1b2c3d4e');

        $manager->update(['registration_city_code' => '21', 'registration_region_code' => '18', 'registration_method' => 4]);
        $migration = $this->migration();
        $migration->up();
        $afterFirst = $this->everyCode();
        $technicianCode = $approved->fresh()->referral_code;
        $this->assertSame($technicianCode, $referrer->fresh()->other_referral_code, 'the referrer link follows the new code');
        $this->assertNotSame('211852025', $technicianCode);

        $migration->up();
        $this->assertSame($afterFirst, $this->everyCode(), 'idempotent');
        $this->assertSame(4, (int) DB::table('registration_code_sequence')->where('id', 1)->value('last_number'), 'the second run consumes no number');

        $migration->down();
        $this->assertSame('211805083', $legacy->fresh()->code);
        $this->assertNull($empty->fresh()->code);
        $this->assertSame('21-18-05-60-3-0.000.007', $dashed->fresh()->code);
        $this->assertSame('211852025', $approved->fresh()->referral_code);
        $this->assertSame('211852025', $referrer->fresh()->other_referral_code);
        $this->assertSame('a1b2c3d4e', $manager->fresh()->personnel_code);
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('registration_code_format_changes', 'reason'));
    }

    public function test_a_legacy_account_is_issued_as_soon_as_its_location_becomes_complete(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 24));
        $legacy = $this->makeUser(['code' => '211805083', 'registration_method' => null]);
        $this->assertSame('211805083', $legacy->fresh()->code, 'nothing is issued while the location is unknown');

        $legacy->update($this->tehran());
        $this->assertSame('211805083', $legacy->fresh()->code);
        app(RegistrationCodeService::class)->issue($legacy, ['city_code' => '21', 'region_code' => '18', 'method' => 3]);

        $this->assertSame('2118056030000001', $legacy->fresh()->code);
        $this->assertSame(3, $legacy->fresh()->registration_method);
    }

    public function test_admin_code_follows_staff_type_and_shares_the_sequence_with_everyone(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 24));
        $service = app(RegistrationCodeService::class);
        $this->assertSame(10, RegistrationCodeService::adminStaffType($this->admin('x1', Admin::STAFF_MANAGER)));
        $this->assertSame(30, RegistrationCodeService::adminStaffType($this->admin('x2', Admin::STAFF_OFFICE)));
        $this->assertSame(31, RegistrationCodeService::adminStaffType($this->admin('x3', Admin::STAFF_FIELD)));

        $manager = $this->admin('x4');
        $field = $this->admin('x5', Admin::STAFF_FIELD);
        $this->assertSame('2118051040000001', $service->issue($manager, ['city_code' => '21', 'region_code' => '18', 'method' => 4]));
        $this->assertSame('2118053140000002', $service->issue($field, ['city_code' => '21', 'region_code' => '18', 'method' => 4]));
        // Issuing again keeps the code and does not consume a number.
        $this->assertSame('2118051040000001', $service->issue($manager, ['city_code' => '21', 'region_code' => '18', 'method' => 4]));
        $this->assertDatabaseHas('registration_code_sequence', ['id' => 1, 'last_number' => 2]);

        // Two customers of the same type and method may not share a code: the sequence skips a taken number.
        DB::table('registration_code_sequence')->where('id', 1)->update(['last_number' => 0]);
        $this->makeUser(['code' => '2118056040000001', 'registration_method' => 4]);
        $user = $this->makeUser(['code' => null, 'registration_method' => null, 'by_who' => 'admin']);
        $taken = $service->issue($user, ['city_code' => '21', 'region_code' => '18', 'method' => 4]);
        $this->assertSame('2118056040000002', $taken);
        $this->assertDatabaseHas('registration_code_sequence', ['id' => 1, 'last_number' => 2]);
    }

    public function test_technician_location_accepts_persian_digits_and_the_city_name(): void
    {
        $this->tehran();
        $service = app(RegistrationCodeService::class);
        $field = $this->technician(['referral_code' => null, 'region' => '۲']);
        $this->assertSame(['city_code' => '21', 'region_code' => '02', 'method' => 1, 'staff_type' => 41], $service->legacyIssueData($field));
        $unknownCity = $this->technician(['referral_code' => null, 'city' => 'Los Angeles']);
        $this->assertNull($service->legacyIssueData($unknownCity));
        $textRegion = $this->technician(['referral_code' => null, 'region' => 'تهران']);
        $this->assertNull($service->legacyIssueData($textRegion));
    }
}
