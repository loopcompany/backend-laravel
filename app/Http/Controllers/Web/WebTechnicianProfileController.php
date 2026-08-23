<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\DTOs\UpdateTechnicianPersonalInfoDTO;
use App\DTOs\UpdateTechnicianVehicleInfoDTO;
use App\DTOs\UpdateTechnicianBankInfoDTO;
use App\DTOs\UpdateTechnicianPasswordDTO;
use App\Http\Requests\UpdateTechnicianPersonalInfoRequest;
use App\Http\Requests\UpdateTechnicianVehicleInfoRequest;
use App\Http\Requests\UpdateTechnicianBankInfoRequest;
use App\Http\Requests\UpdateTechnicianPasswordRequest;
use App\Services\TechnicianService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WebTechnicianProfileController extends Controller
{
    public function __construct(
        protected TechnicianService $technicianService
    ) {}

    /**
     * نمایش پروفایل تکنسین
     */
    public function show()
    {
        /** @var \App\Models\Technician $technician */
        $technician = Auth::guard('technician')->user();
        
        if (!$technician) {
            return redirect()->route('web.technician.login-form');
        }
        
        return view('technician.profile.show', compact('technician'));
    }

    /**
     * نمایش فرم ویرایش اطلاعات شخصی
     */
    public function editPersonalInfo()
    {
        /** @var \App\Models\Technician $technician */
        $technician = Auth::guard('technician')->user();
        
        if (!$technician) {
            return redirect()->route('web.technician.login-form');
        }
        
        return view('technician.profile.edit-personal-info', compact('technician'));
    }

    /**
     * به‌روزرسانی اطلاعات شخصی
     */
    public function updatePersonalInfo(UpdateTechnicianPersonalInfoRequest $request)
    {
        /** @var \App\Models\Technician $technician */
        $technician = Auth::guard('technician')->user();
        
        if (!$technician) {
            return redirect()->route('web.technician.login-form');
        }

        $dto = UpdateTechnicianPersonalInfoDTO::fromArray($request->validated());
        $profilePhoto = $request->file('profile_photo');

        $result = $this->technicianService->updatePersonalInfo($technician, $dto, $profilePhoto);

        if (!$result['success']) {
            return back()
                ->withErrors(['message' => $result['message']])
                ->withInput();
        }

        return redirect()
            ->route('web.technician.profile.show')
            ->with('success', $result['message']);
    }

    /**
     * نمایش فرم ویرایش اطلاعات وسیله نقلیه
     */
    public function editVehicleInfo()
    {
        /** @var \App\Models\Technician $technician */
        $technician = Auth::guard('technician')->user();
        
        if (!$technician) {
            return redirect()->route('web.technician.login-form');
        }
        
        return view('technician.profile.edit-vehicle-info', compact('technician'));
    }

    /**
     * به‌روزرسانی اطلاعات وسیله نقلیه
     */
    public function updateVehicleInfo(UpdateTechnicianVehicleInfoRequest $request)
    {
        /** @var \App\Models\Technician $technician */
        $technician = Auth::guard('technician')->user();
        
        if (!$technician) {
            return redirect()->route('web.technician.login-form');
        }

        $dto = UpdateTechnicianVehicleInfoDTO::fromArray($request->validated());

        $result = $this->technicianService->updateVehicleInfo($technician, $dto);

        if (!$result['success']) {
            return back()
                ->withErrors(['message' => $result['message']])
                ->withInput();
        }

        return redirect()
            ->route('web.technician.profile.show')
            ->with('success', $result['message']);
    }

    /**
     * نمایش فرم ویرایش اطلاعات بانکی
     */
    public function editBankInfo()
    {
        /** @var \App\Models\Technician $technician */
        $technician = Auth::guard('technician')->user();
        
        if (!$technician) {
            return redirect()->route('web.technician.login-form');
        }
        
        return view('technician.profile.edit-bank-info', compact('technician'));
    }

    /**
     * به‌روزرسانی اطلاعات بانکی
     */
    public function updateBankInfo(UpdateTechnicianBankInfoRequest $request)
    {
        /** @var \App\Models\Technician $technician */
        $technician = Auth::guard('technician')->user();
        
        if (!$technician) {
            return redirect()->route('web.technician.login-form');
        }

        $dto = UpdateTechnicianBankInfoDTO::fromArray($request->validated());

        $result = $this->technicianService->updateBankInfo($technician, $dto);

        if (!$result['success']) {
            return back()
                ->withErrors(['message' => $result['message']])
                ->withInput();
        }

        return redirect()
            ->route('web.technician.profile.show')
            ->with('success', $result['message']);
    }

    /**
     * نمایش فرم تغییر رمز عبور
     */
    public function editPassword()
    {
        /** @var \App\Models\Technician $technician */
        $technician = Auth::guard('technician')->user();
        
        if (!$technician) {
            return redirect()->route('web.technician.login-form');
        }
        
        return view('technician.profile.edit-password', compact('technician'));
    }

    /**
     * به‌روزرسانی رمز عبور
     */
    public function updatePassword(UpdateTechnicianPasswordRequest $request)
    {
        /** @var \App\Models\Technician $technician */
        $technician = Auth::guard('technician')->user();
        
        if (!$technician) {
            return redirect()->route('web.technician.login-form');
        }

        $dto = UpdateTechnicianPasswordDTO::fromArray($request->validated());

        $result = $this->technicianService->updatePassword($technician, $dto);

        if (!$result['success']) {
            return back()
                ->withErrors(['message' => $result['message']])
                ->withInput();
        }

        return redirect()
            ->route('web.technician.profile.show')
            ->with('success', $result['message']);
    }

    /**
     * نمایش لیست سفارش‌ها
     */
    public function orders(Request $request)
    {
        /** @var \App\Models\Technician $technician */
        $technician = Auth::guard('technician')->user();
        
        if (!$technician) {
            return redirect()->route('web.technician.login-form');
        }

        $status = $request->query('status');
        $perPage = $request->query('per_page', 15);

        $result = $this->technicianService->getMyOrders($technician, $status, (int)$perPage);

        $orders = $result['data']['orders'] ?? [];

        return view('technician.orders.index', compact('technician', 'orders', 'status'));
    }
}
