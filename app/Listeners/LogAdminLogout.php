<?php

namespace App\Listeners;

use App\Models\Admin;
use App\Models\AdminAuthLog;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Request;

class LogAdminLogout
{
    public function handle(Logout $event): void
    {
        if (! $event->user instanceof Admin) {
            return;
        }

        AdminAuthLog::create([
            'admin_id' => $event->user->id,
            'event' => 'logout',
            'ip' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'logged_at' => now(),
        ]);
    }
}
