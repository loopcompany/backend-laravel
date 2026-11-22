<?php

namespace Tests\Feature;

use App\Services\RegistrationCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesPeople;
use Tests\TestCase;

class RegistrationCodeTest extends TestCase
{
    use RefreshDatabase, MakesPeople;

    private function location(): array
    {
        $province=\App\Models\Province::create(['title'=>'Tehran','code'=>'021']);
        $city=\App\Models\City::create(['title'=>'Tehran','province_id'=>$province->id]);
        $region=\App\Models\Region::create(['code'=>'08','title'=>'district','city_id'=>$city->id]);
        return ['province_id'=>$province->id,'city_id'=>$city->id,'region_id'=>$region->id];
    }

    public function test_new_users_receive_the_pdf_formula_for_all_account_types(): void
    {
        $this->travelTo(now()->setDate(2026,11,13));
        $location=$this->location();
        foreach ([['individual',false,60],['individual',true,61],['company',false,70],['company',true,71],['g_organization',false,80],['s_g_organization',true,81]] as $index=>[$type,$special,$code]) {
            $user=$this->makeUser($location+['code'=>null,'account_type'=>$type,'is_special'=>$special,'by_who'=>'admin','registration_method'=>5,'birth_date'=>'1990-01-01']);
            $this->assertSame('210805'.$code.'50000'.str_pad((string)($index+1),3,'0',STR_PAD_LEFT),$user->code);
        }
        $this->assertDatabaseHas('registration_code_sequence',['id'=>1,'last_number'=>6]);
    }

    public function test_missing_location_is_completed_without_changing_existing_users_or_the_registration_method(): void
    {
        $location=$this->location();
        $new=$this->makeUser(['code'=>null,'registration_method'=>2,'created_at'=>'2025-03-20 08:00:00']);
        $legacy=$this->makeUser(['code'=>'OLD-CODE']);
        $legacyWithoutCode=$this->makeUser(['code'=>null]);
        $this->assertNull($new->code);
        $new->update($location); $new->refresh();
        $this->assertMatchesRegularExpression('/^2108036020000001$/',$new->code);
        $code=$new->code;
        $new->update(['region'=>'20']);
        $this->assertSame($code,$new->fresh()->code);
        $legacy->update($location); $legacyWithoutCode->update($location);
        $this->assertSame('OLD-CODE',$legacy->fresh()->code);
        $this->assertNull($legacyWithoutCode->fresh()->code);
        $this->assertNull($legacy->fresh()->registration_method);
    }

    public function test_method_distinguishes_native_apps_desktop_and_mobile_browsers(): void
    {
        foreach ([['android','okhttp',1],['ios','CFNetwork',1],['web','Mozilla Android Chrome',3],['web','Mozilla Electron/35',2],['desktop','Mozilla',2]] as [$platform,$agent,$expected]) {
            $this->app->instance('request',\Illuminate\Http\Request::create('/api/auth/register','POST',['platform'=>$platform],[],[],['HTTP_USER_AGENT'=>$agent]));
            $this->assertSame($expected,RegistrationCodeService::methodFromRequest());
        }
    }

    public function test_zero_serial_and_old_id_offset_formats_are_not_issued_codes(): void
    {
        $this->assertFalse(RegistrationCodeService::isIssued('21-08-05-60-3-0.000.000'));
        $this->assertFalse(RegistrationCodeService::isIssued('211805112'));
    }

    public function test_sequence_exhaustion_rolls_back_and_never_reuses_a_number(): void
    {
        \Illuminate\Support\Facades\DB::table('registration_code_sequence')->where('id',1)->update(['last_number'=>9999999]);
        $user=$this->makeUser();
        $this->assertThrows(fn()=>app(RegistrationCodeService::class)->issue($user,['city_code'=>'21','region_code'=>'08','method'=>4]),\Illuminate\Validation\ValidationException::class);
        $this->assertDatabaseHas('registration_code_sequence',['id'=>1,'last_number'=>9999999]);
    }

    public function test_public_registration_uses_native_app_method_and_old_reregistration_keeps_its_code(): void
    {
        $this->travelTo(now()->setDate(2026,11,13));
        $this->app->instance(\App\Services\SmsService::class,new \Tests\Fakes\FakeSmsService());
        $location=$this->location();
        $data=$location+['phone'=>'09121112223','melicode'=>'3333333333','email'=>'new@test.local','platform'=>'android'];
        $this->postJson('/api/auth/register',$data)->assertCreated();
        $user=\App\Models\User::where('phone',$data['phone'])->firstOrFail();
        $this->assertSame('2108056010000001',$user->code);
        $this->assertSame(1,$user->registration_method);

        $legacy=$this->makeUser(['phone_verified_at'=>null,'code'=>'OLD-KEEP','melicode'=>'4444444444']);
        $data['phone']=$legacy->phone; $data['melicode']='4444444444'; $data['email']='retry@test.local';
        $this->postJson('/api/auth/register',$data)->assertCreated();
        $this->assertSame('OLD-KEEP',$legacy->fresh()->code);
        $this->assertNull($legacy->fresh()->registration_method);
    }

    public function test_admin_creation_assigns_a_special_code_using_region_code_instead_of_region_id(): void
    {
        $location=$this->location();
        $roles=app(\App\Services\AdminRoleService::class);
        $roles->createBasicPermissions(); $roles->createBasicRoles();
        $admin=\App\Models\Admin::create(['name'=>'Admin','email'=>'code-admin@test.local','password'=>'password','is_active'=>true,'staff_type'=>\App\Models\Admin::STAFF_MANAGER]);
        $admin->assignRole('super-admin'); $this->actingAs($admin,'admin');
        $this->app->instance(\App\Services\SmsService::class,new class extends \Tests\Fakes\FakeSmsService {
            public function sendSecurePassword(string $phone,string $password): bool { return true; }
        });
        \Livewire\Livewire::test(\App\Filament\Resources\UserResource\Pages\CreateUser::class)
            ->fillForm($location+['name'=>'New','last_name'=>'User','phone'=>'09121112224','account_type'=>'individual','is_special'=>true,'registration_method'=>5,'birth_date'=>'1990-01-01'])
            ->call('create')->assertHasNoFormErrors();
        $user=\App\Models\User::where('phone','09121112224')->firstOrFail();
        $this->assertMatchesRegularExpression('/^2108[0-9]{2}6150000001$/',$user->code);
    }

    public function test_company_registration_keeps_the_canonical_code_in_both_user_and_organization(): void
    {
        $this->app->instance(\App\Services\SmsService::class,new \Tests\Fakes\FakeSmsService());
        $location=$this->location();
        $dto=\App\DTOs\OrganizationRegistrationDTO::fromArray($location+[
            'account_type'=>'company','organization_name'=>'Test Company','business_name'=>'Test',
            'agent_name'=>'Agent','agent_phone'=>'09121112225','history'=>'New company',
            'organization_email'=>'company@test.local','organization_phone'=>'02112345678','organization_address'=>'Test address',
            'manager_full_name'=>'Test Manager','manager_national_code'=>'5555555555','melicode'=>'5555555555',
            'manager_mobile'=>'09121112226','manager_birthdate'=>'1990-01-01','postal_code'=>'1234567890','password'=>'temporary-test-only',
        ]);
        $result=app(\App\Services\OrganizationRegistrationService::class)->register($dto);
        $this->assertTrue($result['success'],json_encode($result));
        $user=\App\Models\User::where('phone',$dto->manager_mobile)->firstOrFail();
        $this->assertMatchesRegularExpression('/^2108[0-9]{2}7030000001$/',$user->code);
        $this->assertSame($user->code,$user->organization->organization_code);
    }

    public function test_issuing_twice_keeps_the_same_code_and_preserves_the_next_serial(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 11));
        $first = $this->makeUser(['is_special' => true]);
        $second = $this->makeUser(['account_type' => 'company']);
        $service = app(RegistrationCodeService::class);
        $input = ['city_code' => '21', 'region_code' => '08', 'method' => 4];

        $code = $service->issue($first, $input);
        $this->assertSame('2108056140000001', $code);
        $this->assertSame($code, $first->fresh()->code);
        $this->assertSame($code, $service->issue($first, $input));
        $this->assertSame('2108057040000002', $service->issue($second, $input));
    }
}
