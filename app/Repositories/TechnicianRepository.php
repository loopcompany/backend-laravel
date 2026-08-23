<?php

namespace App\Repositories;

use App\Models\Technician;
use Illuminate\Database\Eloquent\Builder;
use Log;

class TechnicianRepository
{
    public function findByPhone(string $phone): ?Technician
    {
        return Technician::where('phone', $phone)->first();
    }

    public function findUnverifiedByPhone(string $phone): ?Technician
    {
        return Technician::where('phone', $phone)
            ->whereNull('phone_verified_at')
            ->first();
    }

    public function findByReferralCode(string $referralCode): ?Technician
    {
        return Technician::where('referral_code', $referralCode)->first();
    }

    public function create(array $data): Technician
    {
        return Technician::create($data);
    }

    public function update(Technician $technician, array $data): Technician
    {
        $technician->update($data);
        return $technician->fresh();
    }

    public function updateOrCreate(array $conditions, array $data): Technician
    {
        return Technician::updateOrCreate($conditions, $data);
    }

    public function setVerificationCode(Technician $technician, ?string $hashedCode): void
    {
        $technician->update([
            'phone_verify_code' => $hashedCode,
        ]);
    }

    public function verifyPhone(Technician $technician): void
    {
        $technician->update([
            'phone_verified_at' => now(),
            'phone_verify_code' => null, // Clear the code after verification
        ]);
    }

    public function clearVerificationCode(Technician $technician): void
    {
        $technician->update([
            'phone_verify_code' => null,
        ]);
    }

    public function isPhoneUnique(string $phone, ?int $excludeId = null): bool
    {
        $query = Technician::where('phone', $phone);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return !$query->exists();
    }

    public function generateUniqueReferralCode(): string
    {
        do {
            $code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
        } while (Technician::where('referral_code', $code)->exists());

        return $code;
    }

    public function getVerifiedTechnicians(): Builder
    {
        return Technician::whereNotNull('phone_verified_at');
    }

    public function getUnverifiedTechnicians(): Builder
    {
        return Technician::whereNull('phone_verified_at');
    }

    public function attachExpertises(Technician $technician, array $expertiseIds): void
    {
        if (!empty($expertiseIds)) {
            $technician->expertises()->attach($expertiseIds);
        }
    }

    public function syncExpertises(Technician $technician, array $expertiseIds): void
    {
        if (!empty($expertiseIds)) {
            $technician->expertises()->sync($expertiseIds);
        }
    }

    public function updatePersonalInfo(Technician $technician, array $data): Technician
    {
        // اگر کد معرف قبلاً ثبت شده، از آرایه حذف می‌شود
        if ($technician->other_referral_code && isset($data['other_referral_code'])) {
            unset($data['other_referral_code']);
        }

        $technician->update($data);
        return $technician->fresh();
    }

    public function updateVehicleInfo(Technician $technician, array $data): Technician
    {
        $technician->update($data);
        return $technician->fresh();
    }

    public function updateBankInfo(Technician $technician, array $data): Technician
    {
        $technician->update($data);
        return $technician->fresh();
    }

    public function updatePassword(Technician $technician, string $hashedPassword): void
    {
        $technician->update([
            'password' => $hashedPassword,
        ]);
    }
    public function updateAtWork(Technician $technician, int $atWork): void
    {
        $technician->update([
            'at_work' => $atWork,
        ]);
    }

    public function getTechnicianOrders(int $technicianId, ?string $status = null)
    {
        $query = \App\Models\Order::where('technician_id', $technicianId)
            ->with(['user', 'user_address', 'category', 'details', 'extra_services'])
            ->orderBy('created_at', 'desc');
        if ($status || (string) $status == 0) {
            $query->where('status', $status);
        }

        return $query;
    }
    public function createUniquCodeForTechnician($cityCode, $regionCode, $technician, $startWith): string
    {
        // prefix ثابت
        $uniq_code = $cityCode . $regionCode . '0';

        // m = مجموع startWith + id
        $m = $startWith + $technician->id;
        $mStr = (string) $m;

        // رقم اول startWith و m
        $firstDigitMain = (int) ((string) $startWith)[0];
        $firstDigit = (int) $mStr[0];

        // اختلاف
        $diff = $firstDigit - $firstDigitMain; 
        // افزودن صفرهای اضافی
        if ($diff > 0) {
            $uniq_code .= str_repeat('0', $diff);
        }
        $uniq_code .= $firstDigitMain;
        // افزودن بقیه ارقام m به جز رقم اول
        $uniq_code .= substr($mStr, 1);

        return $uniq_code;
    }
}
