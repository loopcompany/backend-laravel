<?php

namespace App\Services;

use App\Models\EmploymentFile;
use App\Models\User;
use App\Models\Technician;
use App\Models\Region;
use App\Models\City;
use App\Models\Province;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Morilog\Jalali\Jalalian;

class RegistrationCodeService
{
    public const METHODS = [
        1 => 'اپلیکیشن',
        2 => 'دسکتاپ',
        3 => 'سایت',
        4 => 'حضوری',
        5 => 'تلفنی',
    ];

    public static function staffTypes(string $personType): array
    {
        return match ($personType) {
            'manager' => [10 => 'مدیر'],
            'staff' => [30 => 'پرسنل اداری', 31 => 'پرسنل میدانی'],
            'technician' => [40 => 'تکنسین اداری', 41 => 'تکنسین میدانی'],
            default => [],
        };
    }

    public static function isIssued(?string $code): bool
    {
        if ($code === null) return false;
        $digits = str_replace(['-', '.'], '', $code);
        return preg_match('/^[0-9]{6}(10|30|31|40|41|60|61|70|71|80|81)[1-5][0-9]{7}$/D', $digits) === 1
            && substr($digits, -7) !== '0000000';
    }

    public static function methodFromRequest(): ?int
    {
        $request=request();
        if (!$request->is('api/auth/register', 'api/organization/register', 'register')) return null;
        $platform=strtolower((string)$request->input('platform'));
        $agent=strtolower((string)$request->userAgent());
        if (in_array($platform,['desktop','electron','windows'],true) || str_contains($agent,'electron')) return 2;
        if (in_array($platform,['android','ios'],true) || str_contains($agent,'okhttp') || str_contains($agent,'cfnetwork') || str_contains($agent,'expo')) return 1;
        return 3; // Browser registration, including a mobile browser.
    }

    private static function twoDigits(mixed $value): ?string
    {
        $digits=ltrim(trim((string)$value),'0');
        return preg_match('/^[0-9]{1,2}$/D',$digits) ? str_pad($digits,2,'0',STR_PAD_LEFT) : null;
    }

    public function location(User $user): ?array
    {
        $region=$user->region_id ? Region::withTrashed()->find($user->region_id) : null;
        $cityId=$region?->city_id ?: $user->city_id;
        $city=$cityId ? City::withTrashed()->find($cityId) : null;
        if (!$city && $user->city) {
            $cities=City::withTrashed()->where('title',$user->city)->get();
            if ($cities->count()===1) $city=$cities->first();
        }
        $provinceId=$city?->province_id ?: $user->province_id;
        $province=$provinceId ? Province::withTrashed()->find($provinceId) : null;
        $cityCode=self::twoDigits($province?->code);
        $regionCode=self::twoDigits($region?->code ?? $user->region);
        return $cityCode && $regionCode ? ['city_code'=>$cityCode,'region_code'=>$regionCode] : null;
    }

    public function issueForNewUser(User $user): ?string
    {
        // Existing accounts have NULL metadata: never renumber them during a profile edit.
        if (!$user->registration_method || filled($user->code)) return $user->code;
        if ($user->is_special && $user->by_who !== 'admin') return null;
        $location=$this->location($user);
        if (!$location) return null; // Wait for real location data rather than inventing 00.
        $code=$this->issue($user,$location+['method'=>(int)$user->registration_method]);
        $user->organization()->where('organization_code','ORG-'.$user->id)->update(['organization_code'=>$code]);
        $user->refresh();
        return $code;
    }

    public function issue(User|EmploymentFile|Technician $record, array $data): string
    {
        return DB::transaction(function () use ($record, $data) {
            $locked = $record->newQuery()->lockForUpdate()->findOrFail($record->getKey());
            $field = match (true) { $locked instanceof User => 'code', $locked instanceof Technician => 'referral_code', default => 'personnel_code' };
            if (self::isIssued($locked->$field)) {
                return $locked->$field;
            }

            $city = (string) ($data['city_code'] ?? '');
            $region = (string) ($data['region_code'] ?? '');
            $method = (int) ($data['method'] ?? 0);
            if (!preg_match('/^[0-9]{2}$/D', $city) || !preg_match('/^[0-9]{2}$/D', $region) || !isset(self::METHODS[$method])) {
                throw ValidationException::withMessages(['city_code' => 'کد شهر و منطقه باید دو رقمی و روش ثبت‌نام معتبر باشد.']);
            }

            if ($locked instanceof User) {
                $type = match ($locked->account_type) {
                    'company' => 70,
                    'organization', 'g_organization', 's_g_organization' => 80,
                    'individual', null => 60,
                    default => throw ValidationException::withMessages(['method' => 'نوع حساب برای صدور کد تعریف نشده است.']),
                };
                $type += (int) $locked->is_special;
                $registeredAt = $locked->created_at;
            } else {
                $type = (int) ($data['staff_type'] ?? 0);
                if (!isset(self::staffTypes($locked instanceof Technician ? 'technician' : $locked->person_type)[$type])) {
                    throw ValidationException::withMessages(['staff_type' => 'نوع انتخاب‌شده با پرونده کارکنان سازگار نیست.']);
                }
                $registeredAt = $locked instanceof Technician ? $locked->created_at : ($locked->cooperationRequest?->created_at ?? $locked->created_at);
            }

            $year = Jalalian::fromDateTime($registeredAt->copy()->setTimezone('Asia/Tehran'))->getYear() % 100;
            $sequence = DB::table('registration_code_sequence')->where('id', 1);
            $number = (int) $sequence->lockForUpdate()->value('last_number');
            do {
                if (++$number > 9999999) {
                    throw ValidationException::withMessages(['method' => 'ظرفیت شماره‌های ترتیبی تکمیل شده است.']);
                }
                $serial = str_pad((string) $number, 7, '0', STR_PAD_LEFT);
                $code = sprintf('%s%s%02d%02d%d%s%s%s', $city, $region, $year, $type, $method,
                    $serial[0], substr($serial, 1, 3), substr($serial, 4, 3));
            } while (User::where('code', $code)->exists() || EmploymentFile::withTrashed()->where('personnel_code', $code)->exists() || Technician::where('referral_code', $code)->exists());

            $sequence->update(['last_number' => $number]);
            $locked->update([$field => $code]);

            return $code;
        }, 3);
    }
}
