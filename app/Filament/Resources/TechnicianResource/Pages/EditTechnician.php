<?php

namespace App\Filament\Resources\TechnicianResource\Pages;

use App\Filament\Resources\TechnicianResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditTechnician extends EditRecord
{
    protected static string $resource = TechnicianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Update approval timestamp and admin when status changes
        if (isset($data['approval_status'])) {
            $currentStatus = $this->record->approval_status;
            
            if ($data['approval_status'] !== $currentStatus) {
                $data['approved_by'] = Auth::id();
                
                if ($data['approval_status'] === 'approved') {
                    $data['approved_at'] = now();
                } elseif ($data['approval_status'] === 'rejected') {
                    $data['approved_at'] = null;
                }
            }
        }

        return $data;
    }
}