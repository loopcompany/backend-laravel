<?php

namespace App\Jobs;

use App\Services\FirebaseNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendFirebaseNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public Authenticatable $notifiable,
        public string $title,
        public string $body,
        public array $data = [],
        public array $options = []
    ) {
    }

    public function handle(FirebaseNotificationService $firebase): void
    {
        $firebase->sendToUser($this->notifiable, $this->title, $this->body, $this->data, $this->options);
    }
}
