<?php

namespace App\Filament\Resources\TechnicianResource\Pages;

use App\Filament\Resources\TechnicianResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateTechnician extends CreateRecord
{
    protected static string $resource = TechnicianResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set the admin who created the technician
        $data['approved_by'] = Auth::id();
        
        // If the technician is being approved during creation
        if ($data['approval_status'] === 'approved') {
            $data['approved_at'] = now();
        }

        return $data;
    }
}