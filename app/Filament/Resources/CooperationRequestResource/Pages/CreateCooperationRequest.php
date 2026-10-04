<?php

namespace App\Filament\Resources\CooperationRequestResource\Pages;

use App\Filament\Resources\CooperationRequestResource;
use App\Models\CooperationRequest;
use Filament\Resources\Pages\CreateRecord;

/**
 * ثبت درخواست همکاری توسط ادمین (مراجعه حضوری، تماس تلفنی، معرفی کارکنان و ...).
 */
class CreateCooperationRequest extends CreateRecord
{
    protected static string $resource = CooperationRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data + [
            'tracking_code' => CooperationRequest::generateTrackingCode(),
            'shahkar_status' => 'unverified',
            'has_certificates' => false,
            'confirmed_at' => now(),
        ];
    }

    protected function afterCreate(): void
    {
        $this->record->logs()->create([
            'admin_id' => auth('admin')->id(),
            'action' => 'submitted',
            'to_status' => $this->record->status,
            'note' => 'ثبت درخواست توسط ادمین — منبع: ' . (\App\Support\Careers\CareerOptions::SOURCES[$this->record->source] ?? $this->record->source),
            'created_at' => now(),
        ]);
    }
}
