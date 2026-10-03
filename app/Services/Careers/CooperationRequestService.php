<?php

namespace App\Services\Careers;

use App\Exceptions\CooperationRequestException;
use App\Models\CooperationRequest;
use App\Services\ShahkarService;
use App\Services\SmsService;
use App\Support\Careers\CareerOptions;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * ثبت و پیگیری درخواست «همکاری با لوپ».
 */
class CooperationRequestService
{
    /** وضعیت‌هایی که پرونده‌ی متقاضی را بسته می‌کنند؛ بعد از آن‌ها ثبت درخواست جدید مجاز است. */
    public const CLOSED_STATUSES = ['rejected', 'withdrawn', 'resume_bank', 'hired'];

    public function __construct(
        private readonly ShahkarService $shahkar,
        private readonly SmsService $sms,
    ) {
    }

    public function submit(array $data, Request $request): CooperationRequest
    {
        $open = CooperationRequest::where('national_code', $data['national_code'])
            ->whereNotIn('status', self::CLOSED_STATUSES)
            ->exists();

        if ($open) {
            throw new CooperationRequestException(
                'برای این کد ملی یک درخواست همکاری در حال بررسی وجود دارد. وضعیت آن را با کد پیگیری استعلام کنید.',
                'ALREADY_APPLIED'
            );
        }

        // استعلام شاهکار: عدم تطابق قطعی رد می‌شود؛ اگر سرویس در دسترس نباشد درخواست «تأییدنشده» ثبت می‌شود
        $inquiry = $this->shahkar->inquiry($data['mobile'], $data['national_code']);
        if ($inquiry['success'] && !$inquiry['matched']) {
            throw new CooperationRequestException(
                'کد ملی با شماره موبایل واردشده مطابقت ندارد. لطفاً شماره‌ای که به نام خودتان ثبت شده را وارد کنید.',
                'SHAHKAR_MISMATCH'
            );
        }

        $isField = $data['job_title'] === 'field_technician';
        $stored = [];

        try {
            $cooperation = DB::transaction(function () use ($data, $request, $inquiry, $isField, &$stored) {
                $cooperation = CooperationRequest::create([
                    'tracking_code' => CooperationRequest::generateTrackingCode(),
                    'status' => 'new',
                    'source' => 'site',
                    'full_name' => $data['full_name'],
                    'mobile' => $data['mobile'],
                    'national_code' => $data['national_code'],
                    'shahkar_status' => $inquiry['success'] ? 'verified' : 'unverified',
                    'city' => $data['city'],
                    'district' => $data['district'] ?? null,
                    'age' => $data['age'],
                    'gender' => $data['gender'],
                    'marital_status' => $data['marital_status'],
                    'military_status' => $data['gender'] === 'male' ? ($data['military_status'] ?? null) : null,
                    'military_status_other' => ($data['military_status'] ?? null) === 'other' ? ($data['military_status_other'] ?? null) : null,
                    'job_title' => $data['job_title'],
                    'job_title_other' => $data['job_title'] === 'other' ? ($data['job_title_other'] ?? null) : null,
                    'cooperation_type' => $data['cooperation_type'],
                    'education_level' => $data['education_level'],
                    'education_level_other' => $data['education_level'] === 'other' ? ($data['education_level_other'] ?? null) : null,
                    'field_of_study' => $data['field_of_study'] ?? null,
                    'work_experience' => $data['work_experience'],
                    'related_experience' => $data['related_experience'],
                    'skills' => $this->normalizeSkills($data['skills'] ?? []),
                    'interest_areas' => array_values(array_unique($data['interest_areas'] ?? [])),
                    'interest_other' => in_array('other', $data['interest_areas'] ?? [], true) ? ($data['interest_other'] ?? null) : null,
                    'has_certificates' => $data['has_certificates'] === 'yes',
                    'extra_skills' => $data['extra_skills'] ?? null,
                    'field_info' => $isField ? $this->normalizeFieldInfo($data['field_info'] ?? []) : null,
                    'start_availability' => $data['start_availability'],
                    'salary_type' => $data['salary_type'],
                    'salary_amount' => $data['salary_type'] === 'fixed' ? ($data['salary_amount'] ?? null) : null,
                    'overtime' => $data['overtime'],
                    'shift_work' => $data['shift_work'],
                    'portfolio_link' => $data['portfolio_link'] ?? null,
                    'confirmed_at' => now(),
                    'ip' => $request->ip(),
                    'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
                ]);

                foreach (['resume' => 'resume_path', 'certificates' => 'certificates_path', 'portfolio' => 'portfolio_path'] as $input => $column) {
                    if (($data[$input] ?? null) instanceof UploadedFile) {
                        $path = $data[$input]->store("cooperation-requests/{$cooperation->id}", CooperationRequest::DISK);
                        $stored[] = $path;
                        $cooperation->{$column} = $path;
                    }
                }
                $cooperation->save();

                $cooperation->logs()->create([
                    'action' => 'submitted',
                    'to_status' => 'new',
                    'note' => 'ثبت درخواست از سایت' . ($inquiry['success'] ? '' : ' (استعلام شاهکار انجام نشد)'),
                    'created_at' => now(),
                ]);

                return $cooperation;
            });
        } catch (Throwable $e) {
            foreach ($stored as $path) {
                \Illuminate\Support\Facades\Storage::disk(CooperationRequest::DISK)->delete($path);
            }
            throw $e;
        }

        try {
            $cooperation->update(['sms_sent' => $this->sms->sendCooperationRequestReceived($cooperation->mobile, $cooperation->tracking_code)]);
        } catch (Throwable $e) {
            Log::warning('cooperation request sms failed', ['id' => $cooperation->id, 'error' => $e->getMessage()]);
        }

        return $cooperation;
    }

    /** استعلام وضعیت با کد پیگیری + موبایل (هر دو لازم است تا کد پیگیری‌های دیگران قابل حدس نباشد). */
    public function track(string $trackingCode, string $mobile): ?CooperationRequest
    {
        $code = strtoupper(trim($trackingCode));
        if (!str_starts_with($code, 'PCS-')) {
            $code = 'PCS-' . ltrim($code, '-');
        }

        return CooperationRequest::where('tracking_code', $code)->where('mobile', $mobile)->first();
    }

    /** مهارت‌های ناشناخته حذف و مهارت‌های انتخاب‌نشده «بدون مهارت» ثبت می‌شوند. */
    private function normalizeSkills(array $skills): array
    {
        $normalized = [];
        foreach (array_keys(CareerOptions::allSkills()) as $key) {
            $level = $skills[$key] ?? 'none';
            $normalized[$key] = array_key_exists($level, CareerOptions::SKILL_LEVELS) ? $level : 'none';
        }

        return $normalized;
    }

    private function normalizeFieldInfo(array $info): array
    {
        return [
            'has_vehicle' => $info['has_vehicle'] ?? null,
            'vehicle_type' => $info['vehicle_type'] ?? null,
            'has_license' => $info['has_license'] ?? null,
            'license_type' => ($info['has_license'] ?? null) === 'yes' ? ($info['license_type'] ?? null) : null,
            'mission_range' => $info['mission_range'] ?? null,
            'carry_equipment' => $info['carry_equipment'] ?? null,
            'onsite_experience' => $info['onsite_experience'] ?? null,
        ];
    }
}
