<?php

namespace App\Filament\Resources\QrCodeResource\Pages;

use App\Filament\Resources\QrCodeResource;
use App\Services\QrCodeService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateQrCode extends CreateRecord
{
    protected static string $resource = QrCodeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate QR Code file
        $fileName = 'qr_' . time() . '_' . uniqid() . '.' . $data['format'];
        $filePath = 'qrcodes/' . $fileName;

        try {
            $qrCodeService = app(QrCodeService::class);
            $qrCodeData = $qrCodeService->generate(
                $data['content'],
                $data['size'],
                $data['format']
            );

            // Save to storage
            Storage::disk('public')->put($filePath, $qrCodeData);

            $data['file_path'] = $filePath;
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('خطا در تولید QR Code')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'QR Code با موفقیت ایجاد شد';
    }
}
