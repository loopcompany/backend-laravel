<?php

namespace App\Filament\Resources\QrCodeResource\Pages;

use App\Filament\Resources\QrCodeResource;
use App\Services\QrCodeService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditQrCode extends EditRecord
{
    protected static string $resource = QrCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('regenerate')
                ->label('تولید مجدد QR Code')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('تولید مجدد QR Code')
                ->modalDescription('آیا مطمئن هستید که می‌خواهید QR Code را با تنظیمات فعلی مجدداً تولید کنید؟')
                ->modalSubmitActionLabel('بله، تولید مجدد کن')
                ->action(function () {
                    $this->regenerateQrCode();
                }),

            Actions\DeleteAction::make()
                ->label('حذف'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Check if content, size, or format changed
        $needsRegeneration = 
            $this->record->content !== $data['content'] ||
            $this->record->size !== $data['size'] ||
            $this->record->format !== $data['format'];

        if ($needsRegeneration) {
            // Delete old file
            if ($this->record->file_path) {
                Storage::disk('public')->delete($this->record->file_path);
            }

            // Generate new QR Code
            $fileName = 'qr_' . time() . '_' . uniqid() . '.' . $data['format'];
            $filePath = 'qrcodes/' . $fileName;

            try {
                $qrCodeService = app(QrCodeService::class);
                $qrCodeData = $qrCodeService->generate(
                    $data['content'],
                    $data['size'],
                    $data['format']
                );

                Storage::disk('public')->put($filePath, $qrCodeData);
                $data['file_path'] = $filePath;
            } catch (\Exception $e) {
                \Filament\Notifications\Notification::make()
                    ->title('خطا در تولید QR Code')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
            }
        }

        return $data;
    }

    protected function regenerateQrCode(): void
    {
        // Delete old file
        if ($this->record->file_path) {
            Storage::disk('public')->delete($this->record->file_path);
        }

        // Generate new QR Code with current settings
        $fileName = 'qr_' . time() . '_' . uniqid() . '.' . $this->record->format;
        $filePath = 'qrcodes/' . $fileName;

        try {
            $qrCodeService = app(QrCodeService::class);
            $qrCodeData = $qrCodeService->generate(
                $this->record->content,
                $this->record->size,
                $this->record->format
            );

            Storage::disk('public')->put($filePath, $qrCodeData);
            
            $this->record->update(['file_path' => $filePath]);

            \Filament\Notifications\Notification::make()
                ->title('QR Code با موفقیت تولید مجدد شد')
                ->success()
                ->send();
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('خطا در تولید QR Code')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'QR Code با موفقیت بروزرسانی شد';
    }
}
