<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Cryptommer\Smsir\Smsir;
use Log;
use Morilog\Jalali\Jalalian;
class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('happy')
                ->label('ارسال تبریک تولد')
                ->icon('heroicon-o-gift')
                ->requiresConfirmation() // برای دیالوگ تأیید
                ->action(function () {

                    // 1) تاریخ امروز شمسی (ماه-روز)
                    $today = Jalalian::now()->format('m-d'); // مثل "01-15"
        
                    // 2) پیداکردن کاربرانی که ماه/روز تولدشان امروز است
                    // اگر birth_date به شکل "YYYY-MM-DD" ذخیره شده:
                    $usersQuery = User::query()
                        ->whereNotNull('phone')
                        ->whereRaw("SUBSTRING(birth_date, 6, 5) = ?", [$today]);

                    $phones = $usersQuery->pluck('phone')->toArray();

                    if (empty($phones)) {
                        Notification::make()
                            ->title('کاربری با تولد امروز یافت نشد.')
                            ->warning()
                            ->send();

                        return;
                    }

                    // 3) SMS انبوه – 100تایی
                    $chunks = array_chunk($phones, 100);

                    $message = 'مشتری عزیز لوپ، تولدتان مبارک! برای شما بهترین‌ها را آرزو می‌کنیم.';

                    foreach ($chunks as $chunk) {
                        try {
                            // این خط را مطابق پنل خودت تنظیم کن
                            Smsir::Send()->Bulk($message, $chunk, time(), '30007732003243');
                        } catch (\Throwable $e) {
                            Log::error("Birthday Bulk SMS Error: {$e->getMessage()}");
                        }
                    }

                    // 4) نوتیفیکیشن موفقیت
                    Notification::make()
                        ->title('پیام تبریک تولد برای ' . count($phones) . ' نفر ارسال شد.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
