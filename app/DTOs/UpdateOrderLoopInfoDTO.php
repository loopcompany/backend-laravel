<?php

namespace App\DTOs;

class UpdateOrderLoopInfoDTO
{
    public function __construct(
        public readonly ?int $duration = null,
        public readonly ?float $loop_cost_estimate = null,
        public readonly ?string $loop_description = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            duration: $data['duration'] ?? null,
            loop_cost_estimate: isset($data['loop_cost_estimate']) ? (float) $data['loop_cost_estimate'] : null,
            loop_description: $data['loop_description'] ?? null,
        );
    }

    public function toArray(): array
    {
        $data = [];
        
        if ($this->duration != null) {
            $data['duration'] = $this->duration;
        }
        
        if ($this->loop_cost_estimate != null) {
            $data['loop_cost_estimate'] = $this->loop_cost_estimate;
        }
        
        if ($this->loop_description != null) {
            $data['loop_description'] = $this->loop_description;
        }
        
        return $data;
    }
}
