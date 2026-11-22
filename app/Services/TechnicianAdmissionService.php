<?php
namespace App\Services;

use App\Helpers\Helper;
use App\Models\Technician;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TechnicianAdmissionService
{
    public static function fields(): array
    {
        return [
            \Filament\Forms\Components\TextInput::make('city_code')->label('کد شهر طبق فرمول')->required()->regex('/^[0-9]{2}$/'),
            \Filament\Forms\Components\TextInput::make('region_code')->label('کد منطقه')->required()->regex('/^[0-9]{2}$/'),
            \Filament\Forms\Components\Select::make('staff_type')->label('گروه تکنسین')->options(RegistrationCodeService::staffTypes('technician'))->required(),
            \Filament\Forms\Components\Select::make('method')->label('روش ثبت‌نام اولیه')->options(RegistrationCodeService::METHODS)->default(1)->required(),
            \Filament\Forms\Components\Checkbox::make('documents_confirmed')->label('مراجعه حضوری، اطلاعات کامل و مدارک این تکنسین بررسی و تأیید شده است')->accepted(),
        ];
    }

    public function admit(Technician $technician, array $data, ?int $adminId): bool
    {
        if (empty($data['documents_confirmed'])) throw ValidationException::withMessages(['documents_confirmed'=>'تأیید مراجعه حضوری و مدارک الزامی است.']);
        $password = Str::password(12, symbols: false);
        DB::transaction(function () use ($technician, $data, $adminId, $password) {
            $locked = Technician::lockForUpdate()->findOrFail($technician->id);
            if ($locked->isApproved()) throw ValidationException::withMessages(['documents_confirmed'=>'این تکنسین قبلاً تأیید شده است؛ برای ارسال دوباره از بازنشانی رمز استفاده کنید.']);
            if (!$locked->phone_verified_at || !$locked->name || !$locked->melicode || !$locked->home_address) {
                throw ValidationException::withMessages(['documents_confirmed'=>'ابتدا شماره موبایل، کد ملی، نام و آدرس تکنسین تکمیل و تأیید شود.']);
            }
            app(RegistrationCodeService::class)->issue($locked, $data);
            $locked->refresh()->update(['password'=>Hash::make($password),'approval_status'=>'approved',
                'approved_at'=>now(),'approved_by'=>$adminId,'rejection_reason'=>null]);
        });
        $technician->refresh();
        $sent = Helper::send_sms($technician->phone, '812565', ['CODE','PASS'], [$technician->referral_code,$password]);
        if ($sent !== true) {
            // Keep the approved account and its random password; allow an explicit resend/reset.
            \Illuminate\Support\Facades\Log::warning('Technician admission SMS was not accepted', ['technician_id'=>$technician->id]);
        }
        return $sent === true;
    }

    public function resetCredentials(Technician $technician): bool
    {
        $password=Str::password(12,symbols:false);
        DB::transaction(function()use($technician,$password){
            $locked=Technician::lockForUpdate()->findOrFail($technician->id);
            if(!$locked->isApproved() || !$locked->phone_verified_at || !$locked->referral_code)throw ValidationException::withMessages(['phone'=>'ابتدا ثبت‌نام و تأیید مدارک و شماره موبایل تکمیل شود.']);
            $locked->update(['password'=>Hash::make($password)]);
            $locked->tokens()->delete();
        });
        $technician->refresh();
        return Helper::send_sms($technician->phone,'812565',['CODE','PASS'],[$technician->referral_code,$password]) === true;
    }
}
