<?php

namespace App\Listeners;

use App\Models\Admin;
use App\Models\AdminAuthLog;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Request;

class LogAdminLogin
{
    public function handle(Login $event): void
    {
        if (! $event->user instanceof Admin) {
            return;
        }
        AdminAuthLog::create([
            'admin_id' => $event->user->id,
            'event' => 'login',
            'ip' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'logged_at' => now(),
        ]);
    }
}
