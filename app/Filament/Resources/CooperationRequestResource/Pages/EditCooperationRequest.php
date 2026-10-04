<?php

namespace App\Filament\Resources\CooperationRequestResource\Pages;

use App\Filament\Resources\CooperationRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/**
 * فرم مدیریت گزینش و مصاحبه. تغییر وضعیت و به‌روزرسانی فرم گزینش در تاریخچه ثبت می‌شوند.
 */
class EditCooperationRequest extends EditRecord
{
    protected static string $resource = CooperationRequestResource::class;

    private ?string $previousStatus = null;

    private ?array $previousRecruitment = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $this->previousStatus = $this->record->status;
        $this->previousRecruitment = $this->record->recruitment;
    }

    protected function afterSave(): void
    {
        $adminId = auth('admin')->id();
        $note = trim((string) ($this->data['status_note'] ?? '')) ?: null;

        if ($this->previousStatus !== $this->record->status) {
            $this->record->logs()->create([
                'admin_id' => $adminId,
                'action' => 'status_changed',
                'from_status' => $this->previousStatus,
                'to_status' => $this->record->status,
                'note' => $note,
                'created_at' => now(),
            ]);
        } elseif ($note !== null) {
            $this->record->logs()->create(['admin_id' => $adminId, 'action' => 'note', 'note' => $note, 'created_at' => now()]);
        }

        if ($this->previousRecruitment != $this->record->recruitment) {
            $sections = collect($this->record->recruitment ?? [])
                ->filter(fn ($value, $key) => ($this->previousRecruitment[$key] ?? null) != $value)
                ->keys()
                ->map(fn ($key) => self::SECTION_LABELS[$key] ?? $key)
                ->implode('، ');

            $this->record->logs()->create([
                'admin_id' => $adminId,
                'action' => 'recruitment_updated',
                'result' => $sections ?: null,
                'created_at' => now(),
            ]);
        }

        $this->data['status_note'] = null;
    }

    private const SECTION_LABELS = [
        'screening' => 'بررسی رزومه',
        'contact' => 'تماس و دعوت',
        'interviews' => 'مصاحبه',
        'test' => 'آزمون تخصصی',
        'decision' => 'تصمیم نهایی',
        'offer' => 'پیشنهاد همکاری',
        'hiring' => 'تشکیل پرونده',
        'final' => 'تأیید نهایی',
    ];
}
