<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Helpers\Helper;
use App\Models\Order;
use App\Services\OrderService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use PHPUnit\TextUI\Help;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    public ?array $oldData = null;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // ذخیره وضعیت قبلی
        $this->oldData = $data;
        return $data;
    }

    protected function afterSave(): void
    {
        // بررسی تغییر وضعیت به "انجام شده" (2)
        if(is_null($this->oldData['prepayment']) && $this->record->prepayment){
            $user = $this->record->user;
            if($user){
                Helper::send_sms($user->phone, 899612, ['ID'], [$this->record->id]);
            }
        }
        if($this->oldData['prepayment_payment_status'] ==0 && $this->record->prepayment_payment_status == 1){
            $this->record->update(['user_accept_date' => now()]);
        }
        if ($this->oldData && isset($this->oldData['status']) && $this->record->status == 2 && $this->oldData['status'] != 2) {


            // اگر finished_at خالی باشه، الان ست کن
            if (!$this->record->finished_at) {
                $this->record->update(['finished_at' => now()]);
            }

            // پردازش پرداخت به تکنسین
            $orderService = app(OrderService::class);
            $paymentResult = $orderService->processTechnicianPayment($this->record);

            if ($paymentResult['success']) {
                Notification::make()
                    ->title('پرداخت به تکنسین انجام شد')
                    ->body('سهم تکنسین با موفقیت به کیف پول او واریز شد.')
                    ->success()
                    ->send();
            } else {
                // فقط در صورتی که خطای مهمی باشه نوتیفیکیشن بده
                if (!in_array($paymentResult['error_code'] ?? '', ['NO_TECHNICIAN', 'NOT_PAID', 'ALREADY_PAID_TO_TECHNICIAN'])) {
                    Notification::make()
                        ->title('خطا در پرداخت به تکنسین')
                        ->body($paymentResult['message'])
                        ->warning()
                        ->send();
                }
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
