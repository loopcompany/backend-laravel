<?php

namespace App\Services;

use App\Models\Admin;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class AdminPanelNotificationService
{
    public function sendToAdmins(string $title, string $body): void
    {
        Admin::query()
            ->where('is_active', true)
            ->get()
            ->each(function (Admin $admin) use ($title, $body): void {
                try {
                    Notification::make()
                        ->title($title)
                        ->body($body)
                        ->warning()
                        ->sendToDatabase($admin);
                } catch (\Throwable $exception) {
                    Log::warning('Admin panel notification could not be stored.', [
                        'admin_id' => $admin->id,
                        'title' => $title,
                        'error' => $exception->getMessage(),
                    ]);
                }
            });
    }
}
