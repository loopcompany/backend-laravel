<?php

namespace App\Services;

use App\Events\RealtimeChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RealtimePublisher
{
    private bool $buffering = false;
    private array $pending = [];

    public function begin(): void
    {
        $this->buffering = true;
    }

    public function changed(string $channel, array $topics): void
    {
        if (!config('realtime.enabled')) return;
        DB::afterCommit(function () use ($channel, $topics) {
            $this->pending[$channel] = array_unique(array_merge($this->pending[$channel] ?? [], $topics));
            if (!$this->buffering) $this->flush();
        });
    }

    public function flush(): void
    {
        $pending = $this->pending;
        $this->pending = [];
        $this->buffering = false;
        foreach ($pending as $channel => $topics) {
            try {
                event(new RealtimeChanged($channel, $topics));
            } catch (\Throwable $exception) {
                // A committed business operation must survive a websocket outage.
                Log::warning('Realtime delivery failed', ['channel' => $channel, 'error' => $exception->getMessage()]);
            }
        }
    }
}
