<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Repositories\UserRepository;
use App\Services\SecurePasswordService;
use App\Services\SmsService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    private ?string $generatedPassword = null;
    private ?string $generatedReferralCode = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // حذف داده‌های organization از داده اصلی user
        if (isset($data['organization'])) {
            unset($data['organization']);
        }
        $data['by_who'] = 'admin';

        // تولید خودکار کد معرف با استفاده از الگوریتم مشابه ثبت‌نام کاربر
        $userRepository = app(UserRepository::class);
        $this->generatedReferralCode = $userRepository->generateUniqueReferralCode();

        $data['referral_code'] = $this->generatedReferralCode;

        // تولید خودکار رمز عبور امن در صورت خالی بودن
        if (empty($data['password'])) {
            $securePasswordService = app(SecurePasswordService::class);
            $this->generatedPassword = $securePasswordService->generateSecurePassword();
            $data['password'] = bcrypt($this->generatedPassword);
        } else {
            // اگر ادمین رمز وارد کرده، آن را ذخیره کنیم
            $this->generatedPassword = $data['password'];
            $data['password'] = bcrypt($data['password']);
        }

        // حذف password_confirmation از داده‌ها
        unset($data['password_confirmation']);

        return $data;
    }

    protected function afterCreate(): void
    {
        // ذخیره اطلاعات سازمان اگر نوع حساب سازمانی باشد
        $data = $this->form->getState();
        $userRepository = app(UserRepository::class);

        if ($this->record->account_type !== 'individual' && isset($data['organization'])) {
            $this->record->organization()->updateOrCreate(
                ['user_id' => $this->record->id],
                $data['organization']
            );
            if ($this->record->account_type == 'company' && $this->record->is_special == 0) {

                $code = $userRepository->createUniquCodeForUsers(21, $data['region_id'], $this->record, 7000000);
                $this->record->update([
                    'code' => $code,
                ]);
            } else if ($this->record->is_special == 0) {
                $code = $userRepository->createUniquCodeForUsers(21, $data['region_id'], $this->record, 9000000);
                $this->record->update([
                    'code' => $code,
                ]);

            }
        } else if ($this->record->is_special == 0) {

            $code = $userRepository->createUniquCodeForUsers(21, $data['region_id'], $this->record, 5000);
            $this->record->update([
                'code' => $code,
            ]);
        }

        // ارسال SMS با کد معرف و رمز عبور به کاربر
        if ($this->generatedPassword && $this->generatedReferralCode) {
            try {
                $smsService = app(SmsService::class);

                // ارسال SMS با استفاده از قالب موجود sendSecurePassword
                // در این SMS هم رمز عبور و هم کد معرف ارسال می‌شود
                $message = "کد معرف: {$this->generatedReferralCode}\nرمز عبور: {$this->generatedPassword}";

                // استفاده از قالب SMS موجود برای ارسال رمز عبور
                $smsSent = $smsService->sendSecurePassword(
                    $this->record->phone,
                    $this->generatedPassword
                );

                if ($smsSent) {
                    Log::info('Admin created user - SMS sent successfully', [
                        'user_id' => $this->record->id,
                        'phone' => $this->record->phone,
                        'referral_code' => $this->generatedReferralCode,
                        'account_type' => $this->record->account_type
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('کاربر با موفقیت ایجاد شد')
                        ->body("کد معرف: {$this->generatedReferralCode} و رمز عبور به شماره {$this->record->phone} ارسال شد.")
                        ->send();
                } else {
                    Log::error('Admin created user - SMS send failed', [
                        'user_id' => $this->record->id,
                        'phone' => $this->record->phone,
                        'referral_code' => $this->generatedReferralCode
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->warning()
                        ->title('کاربر ایجاد شد اما SMS ارسال نشد')
                        ->body("کد معرف: {$this->generatedReferralCode} - لطفاً به صورت دستی به کاربر اطلاع دهید.")
                        ->send();
                }

            } catch (\Exception $e) {
                Log::error('Admin created user - SMS error', [
                    'user_id' => $this->record->id,
                    'phone' => $this->record->phone,
                    'error' => $e->getMessage()
                ]);

                \Filament\Notifications\Notification::make()
                    ->danger()
                    ->title('خطا در ارسال SMS')
                    ->body("کاربر ایجاد شد اما SMS ارسال نشد. کد معرف: {$this->generatedReferralCode}")
                    ->send();
            }
        }
    }
}
