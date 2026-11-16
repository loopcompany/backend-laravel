<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class RealtimeChanged implements ShouldBroadcastNow
{
    public function __construct(private string $channel, private array $topics) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel($this->channel)];
    }

    public function broadcastAs(): string
    {
        return 'data.changed';
    }

    public function broadcastWith(): array
    {
        return ['topics' => array_values(array_unique($this->topics))];
    }
}
