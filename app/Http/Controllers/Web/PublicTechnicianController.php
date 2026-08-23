<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Technician;
use Illuminate\Http\Request;

class PublicTechnicianController extends Controller
{
    /**
     * نمایش عمومی اطلاعات تکنسین (برای QR Code)
     * 
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        // دریافت اطلاعات تکنسین
        $technician = Technician::find($id);

        // اگر تکنسین یافت نشد یا دسترسی ندارد
        if (!$technician || !$technician->has_access) {
            return view('main.technician-profile', [
                'technician' => null,
                'ordersCount' => 0,
                'averageRating' => 0,
            ]);
        }

        // محاسبه آمار
        $ordersCount = $technician->orders()
            ->whereIn('status', [2, 3]) // تکمیل شده یا تحویل داده شده
            ->count();

        // محاسبه میانگین امتیاز تکنسین
        $averageRating = $technician->technicianReviews()
            ->avg('technician_rate') ?? 0;

        return view('main.technician-profile', [
            'technician' => $technician,
            'ordersCount' => $ordersCount,
            'averageRating' => $averageRating,
        ]);
    }
}
