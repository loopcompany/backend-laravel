<?php

namespace App\Filament\Resources\EditRequestResource\Pages;

use App\Filament\Resources\EditRequestResource;
use App\Models\EditRequest;
use App\Services\EditRequestApprovalService;
use Closure;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;
use Throwable;

class EditEditRequest extends EditRecord
{
    protected static string $resource = EditRequestResource::class;

    public function getTitle(): string
    {
        return 'بررسی درخواست ویرایش سازمان';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label('تأیید و اعمال تغییرات')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(
                    fn (): bool =>
                    $this->record->status === EditRequest::STATUS_PENDING
                )
                ->requiresConfirmation()
                ->modalHeading('تأیید درخواست ویرایش')
                ->modalDescription(
                    'پس از تأیید، اطلاعات کاربر و سازمان با مقادیر درخواستی جایگزین می‌شوند.'
                )
                ->modalSubmitActionLabel('تأیید و اعمال')
                ->action(function (): void {
                    $this->executeAction(
                        callback: fn () =>
                            app(EditRequestApprovalService::class)
                                ->approve($this->record),

                        successMessage: 'درخواست تأیید شد و اطلاعات جدید اعمال شدند.'
                    );
                }),

            Actions\Action::make('reject')
                ->label('رد درخواست')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(
                    fn (): bool =>
                    $this->record->status === EditRequest::STATUS_PENDING
                )
                ->requiresConfirmation()
                ->modalHeading('رد درخواست ویرایش')
                ->modalDescription(
                    'با رد این درخواست هیچ تغییری در اطلاعات کاربر یا سازمان ایجاد نمی‌شود.'
                )
                ->modalSubmitActionLabel('رد درخواست')
                ->action(function (): void {
                    $this->executeAction(
                        callback: fn () =>
                            app(EditRequestApprovalService::class)
                                ->reject($this->record),

                        successMessage: 'درخواست ویرایش رد شد.'
                    );
                }),
        ];
    }

    /**
     * دکمه ذخیره پیش‌فرض Filament حذف می‌شود،
     * چون اطلاعات فقط باید تأیید یا رد شوند.
     */
    protected function getFormActions(): array
    {
        return [];
    }

    private function executeAction(
        Closure $callback,
        string $successMessage
    ): void {
        try {
            $callback();
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())
                ->flatten()
                ->first() ?? $exception->getMessage();

            Notification::make()
                ->danger()
                ->title('عملیات انجام نشد')
                ->body($message)
                ->send();

            return;
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->danger()
                ->title('خطای غیرمنتظره')
                ->body('هنگام پردازش درخواست خطایی رخ داد. گزارش خطا ثبت شد.')
                ->send();

            return;
        }

        Notification::make()
            ->success()
            ->title('عملیات موفق')
            ->body($successMessage)
            ->send();

        $this->redirect(EditRequestResource::getUrl('index'));
    }
}