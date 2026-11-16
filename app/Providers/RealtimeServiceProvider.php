<?php

namespace App\Providers;

use App\Models\Admin;
use App\Models\Technician;
use App\Models\User;
use App\Observers\RealtimeObserver;
use App\Services\RealtimeAccess;
use App\Services\RealtimePublisher;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class RealtimeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(RealtimePublisher::class);
    }

    public function boot(): void
    {
        Broadcast::channel('users.{id}', fn ($actor, $id) => $actor instanceof User && (string) $actor->id === (string) $id, ['guards' => ['sanctum']]);
        Broadcast::channel('technicians.{id}', fn ($actor, $id) => $actor instanceof Technician && (string) $actor->id === (string) $id, ['guards' => ['sanctum']]);
        Broadcast::channel('catalog', fn ($actor) => $actor instanceof User || $actor instanceof Technician, ['guards' => ['sanctum']]);
        Broadcast::channel('admins.{id}', fn ($actor, $id) => $actor instanceof Admin && $actor->is_active !== false && (string) $actor->id === (string) $id, ['guards' => ['admin']]);
        Broadcast::channel('admin.{topic}', fn ($actor, $topic) => $actor instanceof Admin && in_array($topic, RealtimeAccess::adminTopics($actor), true), ['guards' => ['admin']]);

        foreach (RealtimeObserver::TOPICS as $name => $topic) {
            $class = 'App\\Models\\' . $name;
            if (class_exists($class)) $class::observe(RealtimeObserver::class);
        }
        \Illuminate\Notifications\DatabaseNotification::observe(RealtimeObserver::class);
    }
}
