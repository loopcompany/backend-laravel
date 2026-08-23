<?php

namespace App\Filament\Resources\IncentivePlanResource\Pages;

use App\Filament\Resources\IncentivePlanResource;
use App\Models\IncentivePlan;
use App\Models\Technician;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Filament\Support\Exceptions\Halt;

class CreateIncentivePlan extends CreateRecord
{
    protected static string $resource = IncentivePlanResource::class;

    protected int $createdCount = 0;

    protected function handleRecordCreation(array $data): Model
    {
        $sendMode = $data['send_mode'] ?? 'manual';

        $technicianIds = match ($sendMode) {
            'field' => Technician::query()
                ->where('technician_type', 'تکنسین میدانی')
                ->where('approval_status', 'approved')
                ->where('has_access', 1)
                ->pluck('id')
                ->toArray(),

            'internal' => Technician::query()
                ->where('technician_type', 'تکنسین داخلی')
                ->where('approval_status', 'approved')
                ->where('has_access', 1)
                ->pluck('id')
                ->toArray(),

            default => $data['technician_ids'] ?? [],
        };

        $technicianIds = array_values(array_unique(array_filter($technicianIds)));

        if (count($technicianIds) === 0) {
            Notification::make()
                ->danger()
                ->title('خطا در ثبت طرح')
                ->body(match ($sendMode) {
                    'field' => 'هیچ تکنسین میدانیِ تاییدشده و دارای دسترسی پیدا نشد.',
                    'internal' => 'هیچ تکنسین داخلیِ تاییدشده و دارای دسترسی پیدا نشد.',
                    default => 'حداقل یک تکنسین را انتخاب کنید.',
                })
                ->send();

            throw new Halt();
        }
        $firstRecord = null;

        DB::transaction(function () use ($data, $technicianIds, &$firstRecord) {
            foreach ($technicianIds as $technicianId) {
                $record = IncentivePlan::query()->create([
                    'technician_id' => $technicianId,
                    'description' => $data['description'],
                    'end_at' => $data['end_at'] ?? null,
                    'status' => $data['status'] ?? IncentivePlan::STATUS_PENDING,
                ]);

                if ($firstRecord === null) {
                    $firstRecord = $record;
                }

                $this->createdCount++;
            }
        });

        return $firstRecord;
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('طرح تشویقی ثبت شد')
            ->body($this->createdCount . ' طرح تشویقی با موفقیت ایجاد شد.');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
