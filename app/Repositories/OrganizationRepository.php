<?php

namespace App\Repositories;

use App\Models\EditRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrganizationRepository
{
    /**
     * ایجاد سازمان جدید
     */
    public function create(array $data): Organization
    {
        return Organization::create($data);
    }

    /**
     * پیدا کردن سازمان بر اساس user_id
     */
    public function findByUserId(int $userId): ?Organization
    {
        return Organization::where('user_id', $userId)->first();
    }
    public function findEditRequestByUserId(int $userId): ?EditRequest
    {
        return EditRequest::where('user_id', $userId)->where('status', '!=', '1')->first();
    }

    /**
     * پیدا کردن سازمان بر اساس organization_code
     */
    public function findByOrganizationCode(string $organizationCode): ?Organization
    {
        return Organization::where('organization_code', $organizationCode)->first();
    }

    /**
     * پیدا کردن سازمان بر اساس کد ملی مدیر
     */
    public function findByManagerNationalCode(string $nationalCode): ?Organization
    {
        return Organization::where('manager_national_code', $nationalCode)->first();
    }

    /**
     * به‌روزرسانی اطلاعات سازمان
     */
    public function update(int $id, array $data): ?Organization
    {
        $organization = Organization::find($id);
        
        if (!$organization) {
            return null;
        }

        $organization->update($data);
        return $organization->fresh();
    }

    /**
     * تولید کد سازمانی یونیک (6 رقمی)
     */
    public function generateUniqueOrganizationCode(): string
    {
        do {
            // تولید عدد 6 رقمی تصادفی
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            
            // بررسی یونیک بودن
            $exists = Organization::where('organization_code', $code)->exists();
            
        } while ($exists);

        return $code;
    }

    /**
     * دریافت تمام سازمان‌ها با pagination
     */
    public function getAllPaginated(int $perPage = 15)
    {
        return Organization::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * حذف سازمان
     */
    public function delete(int $id): bool
    {
        $organization = Organization::find($id);
        
        if (!$organization) {
            return false;
        }

        return $organization->delete();
    }

    /**
     * جستجوی سازمان‌ها
     */
    public function search(string $query)
    {
        return Organization::with('user')
            ->where('organization_name', 'like', '%' . $query . '%')
            ->orWhere('organization_code', 'like', '%' . $query . '%')
            ->orWhere('manager_full_name', 'like', '%' . $query . '%')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }
}
