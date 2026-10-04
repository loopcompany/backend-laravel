<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\CooperationRequestException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCooperationRequest;
use App\Services\Careers\CooperationRequestService;
use App\Support\Careers\CareerOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * صفحه‌ی «همکاری با لوپ» در سایت: معرفی، فرم درخواست همکاری و استعلام کد پیگیری.
 */
class CareersController extends Controller
{
    public function __construct(private readonly CooperationRequestService $service)
    {
    }

    public function index(): View
    {
        return view('main.careers');
    }

    public function store(StoreCooperationRequest $request): RedirectResponse
    {
        try {
            $cooperation = $this->service->submit($request->validated(), $request);
        } catch (CooperationRequestException $e) {
            return redirect()->to(route('web.careers') . '#apply')->withErrors(['form' => $e->getMessage()])->withInput();
        }

        return redirect()->to(route('web.careers') . '#apply')->with('cooperation_submitted', [
            'tracking_code' => $cooperation->tracking_code,
            'sms_sent' => $cooperation->sms_sent,
        ]);
    }

    public function track(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tracking_code' => ['required', 'string', 'max:20'],
            'track_mobile' => ['required', 'regex:/^09[0-9]{9}$/'],
        ], [
            'tracking_code.required' => 'کد پیگیری را وارد کنید.',
            'track_mobile.required' => 'شماره موبایل ثبت‌شده در درخواست را وارد کنید.',
            'track_mobile.regex' => 'شماره موبایل باید با 09 شروع شده و ۱۱ رقم باشد.',
        ]);

        $cooperation = $this->service->track($data['tracking_code'], $data['track_mobile']);

        if (!$cooperation) {
            return redirect()->to(route('web.careers') . '#track')
                ->withErrors(['tracking_code' => 'درخواستی با این کد پیگیری و شماره موبایل پیدا نشد.'], 'track')
                ->withInput();
        }

        return redirect()->to(route('web.careers') . '#track')->with('cooperation_tracked', [
            'tracking_code' => $cooperation->tracking_code,
            'job_title' => $cooperation->jobTitleLabel(),
            'submitted_at' => \Morilog\Jalali\Jalalian::fromCarbon($cooperation->created_at)->format('Y/m/d'),
            'status_message' => CareerOptions::PUBLIC_STATUSES[$cooperation->status] ?? 'درخواست شما در حال بررسی است.',
        ]);
    }
}
