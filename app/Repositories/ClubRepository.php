<?php

namespace App\Repositories;

use App\Models\Club;
use App\Models\DiscountCode;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ClubRepository
{
    /**
     * دریافت کلاب‌های هفته جاری
     */
    public function getWeeklyClubs(): Collection
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        
        return Club::whereBetween('updated_at', [$startOfWeek, $endOfWeek])->get();
    }

    /**
     * دریافت تمام کلاب‌ها با category
     */
    public function getAllClubsWithCategory(): Collection
    {
        return Club::with('category')->get();
    }

    /**
     * دریافت کلاب با تخفیف تایم‌دار فعال
     */
    public function getActiveTimedDiscount(): ?Club
    {
        return Club::where('application_discount', 1)
            ->where('expired_at', '>', now())
            ->first();
    }

    /**
     * دریافت جزئیات کلاب با شناسه
     */
    public function findById(int $clubId): ?Club
    {
        return Club::find($clubId);
    }

    /**
     * دریافت کلاب با category
     */
    public function findWithCategory(int $clubId): ?Club
    {
        return Club::with('category')->find($clubId);
    }
}
