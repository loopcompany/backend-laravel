<?php
namespace App\Events;
class AppReleasesChanged implements \Illuminate\Contracts\Broadcasting\ShouldBroadcastNow
{
    public function broadcastOn(): array { return [new \Illuminate\Broadcasting\Channel('app-updates')]; }
    public function broadcastAs(): string { return 'data.changed'; }
    public function broadcastWith(): array { return ['topics' => ['app-updates']]; }
}
