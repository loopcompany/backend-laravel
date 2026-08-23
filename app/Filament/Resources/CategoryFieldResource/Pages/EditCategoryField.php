<?php

namespace App\Filament\Resources\CategoryFieldResource\Pages;

use App\Filament\Resources\CategoryFieldResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCategoryField extends EditRecord
{
    protected static string $resource = CategoryFieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    public function getHeading(): string
    {
        return 'ساخت مراحل شرطی' ;
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
