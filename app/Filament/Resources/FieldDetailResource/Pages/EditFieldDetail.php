<?php

namespace App\Filament\Resources\FieldDetailResource\Pages;

use App\Filament\Resources\FieldDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFieldDetail extends EditRecord
{
    protected static string $resource = FieldDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }


    public function getHeading(): string
    {
        return 'مدیریت جداول' ;
    }



    protected function getFormActions(): array
    {
        return []; // حذف دکمه‌های ذخیره و لغو
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }
}
