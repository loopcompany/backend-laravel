<?php
namespace App\Events;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
class LocalizationChanged implements ShouldBroadcastNow {
 public function __construct(public int $revision){}
 public function broadcastOn():array{return [new Channel('localization')];}
 public function broadcastAs():string{return 'data.changed';}
 public function broadcastWith():array{return ['topics'=>['localization'],'revision'=>$this->revision];}
}
