<?php

namespace App\Filament\Resources\EmploymentFileResource\Pages;

use App\Filament\Resources\EmploymentFileResource;
use App\Models\EmploymentFile;
use Illuminate\Support\Arr;

/**
 * بخش محرمانه فقط برای ادمین مجاز پر و ذخیره می‌شود؛ تغییرات (فقط نام بخش‌ها) در تاریخچه ثبت می‌شود.
 */
trait HandlesConfidentialSection
{
    private const TRACKED = ['personnel_code', 'person_type', 'full_name', 'national_code', 'mobile', 'employment_status', 'file_status', 'general', 'attachments'];

    private const SECTION_LABELS = [
        'personnel_code' => 'اطلاعات هویتی',
        'person_type' => 'اطلاعات هویتی',
        'full_name' => 'اطلاعات هویتی',
        'national_code' => 'اطلاعات هویتی',
        'mobile' => 'اطلاعات هویتی',
        'general.identity' => 'اطلاعات هویتی',
        'general.education' => 'اطلاعات تحصیلی و تخصصی',
        'general.employment' => 'اطلاعات استخدامی و سازمانی',
        'employment_status' => 'اطلاعات استخدامی و سازمانی',
        'general.technician' => 'اطلاعات اختصاصی تکنسین',
        'general.documents' => 'مدارک و مستندات',
        'attachments' => 'مدارک و مستندات',
        'general.access' => 'دسترسی‌ها و تجهیزات',
        'general.onboarding' => 'آموزش و شروع به کار',
        'general.approval' => 'تأیید و تکمیل پرونده',
        'file_status' => 'تأیید و تکمیل پرونده',
        'confidential' => 'اطلاعات مالی و حقوقی (محرمانه)',
    ];

    protected function fillConfidential(array $data): array
    {
        // confidential در $hidden مدل است و در attributesToArray نمی‌آید
        if (EmploymentFileResource::canSeeConfidential()) {
            $data['confidential'] = $this->record?->confidential ?? [];
        } else {
            unset($data['confidential']);
        }

        return $data;
    }

    protected function guardConfidential(array $data): array
    {
        if (!EmploymentFileResource::canSeeConfidential()) {
            unset($data['confidential']);
        }

        $data['updated_by'] = auth('admin')->id();

        return $data;
    }

    protected function snapshot(EmploymentFile $file): array
    {
        return ['tracked' => Arr::dot($file->only(self::TRACKED)), 'confidential' => $file->confidential];
    }

    protected function logChanges(EmploymentFile $file, array $before): void
    {
        $file->refresh();
        $after = $this->snapshot($file);

        $keys = array_unique(array_merge(array_keys($after['tracked']), array_keys($before['tracked'])));
        $sections = collect($keys)
            ->filter(fn ($key) => ($after['tracked'][$key] ?? null) != ($before['tracked'][$key] ?? null))
            ->map(fn ($key) => self::SECTION_LABELS[implode('.', array_slice(explode('.', $key), 0, str_starts_with($key, 'general.') ? 2 : 1))] ?? $key);

        if ($after['confidential'] != $before['confidential']) {
            $sections->push(self::SECTION_LABELS['confidential']);
        }

        $sections = $sections->unique()->values();

        if ($sections->isEmpty()) {
            return;
        }

        $file->logs()->create([
            'admin_id' => auth('admin')->id(),
            'action' => 'updated',
            'changed_fields' => $sections->all(),
            'created_at' => now(),
        ]);
    }
}
