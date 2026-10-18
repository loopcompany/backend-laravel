<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Helpers\Helper;
use App\Models\Order;
use App\Services\OrderService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

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

    protected function beforeSave(): void
    {
        // محصول اعزام‌شده به لوپ: پیش از پرداخت پیش‌پرداخت، تعمیر شروع نمی‌شود و سفارش انجام‌شده ثبت نمی‌شود
        $state = $this->form->getRawState();

        // لغو از پنل همیشه «لغو از سوی پنل» (۵) است؛ ۳/۴/۶ فقط از اپ کاربر، اپ تکنسین و سیستم ثبت می‌شوند
        if (isset($state['status']) && OrderResource::isPanelLockedStatus((int) $state['status'], $this->record)) {
            Notification::make()
                ->title('این وضعیت از پنل قابل ثبت نیست')
                ->body('برای لغو سفارش از پنل، «لغو از سوی پنل» را انتخاب کنید.')
                ->danger()
                ->send();

            $this->halt();
        }

        $wantsDone = (int) ($state['status'] ?? $this->record->status) === 2 && (int) $this->record->status !== 2;
        $prepaymentPaid = (int) ($state['prepayment_payment_status'] ?? $this->record->prepayment_payment_status) === 1;

        if (OrderResource::awaitingLoopPrepayment($this->record) && !$prepaymentPaid && $wantsDone) {
            Notification::make()
                ->title('پیش‌پرداخت پرداخت نشده است')
                ->body('تا وقتی کاربر پیش‌پرداخت را پرداخت نکرده، تعمیر شروع نمی‌شود و سفارش «انجام شده» ثبت نمی‌شود.')
                ->danger()
                ->send();

            $this->halt();
        }
    }

    protected function afterSave(): void
    {
        // پیامک «برآورد هزینه آماده است»: وقتی برآورد و درصد پیش‌پرداخت هر دو ثبت شدند.
        // درصد پیش‌پرداخت از هنگام اعزام به لوپ پیش‌فرض ۶۰ دارد، پس ثبت برآورد هم ملاک است.
        $estimateWasMissing = is_null($this->oldData['prepayment']) || is_null($this->oldData['loop_cost_estimate'] ?? null);
        if ($estimateWasMissing && $this->record->prepayment && $this->record->loop_cost_estimate) {
            $user = $this->record->user;
            if($user){
                Helper::send_sms($user->phone, 899612, ['ID'], [$this->record->id]);
            }
        }
        if($this->oldData['prepayment_payment_status'] ==0 && $this->record->prepayment_payment_status == 1){
            $this->record->update(['user_accept_date' => now()]);
        }
        // بررسی تغییر وضعیت به "انجام شده" (2)
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
