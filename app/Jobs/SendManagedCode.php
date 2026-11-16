<?php
namespace App\Jobs;

use App\Services\CodeDeliveryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendManagedCode implements ShouldQueue
{
    use Queueable;
    public int $tries = 1;
    public int $timeout = 30;
    public function __construct(public int $deliveryId) {}
    public function handle(CodeDeliveryService $service): void { $service->send($this->deliveryId); }
}
