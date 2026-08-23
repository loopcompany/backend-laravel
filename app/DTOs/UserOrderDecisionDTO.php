<?php

namespace App\DTOs;

class UserOrderDecisionDTO
{
    public function __construct(
        public readonly string $decision,
        public readonly ?string $reason = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            decision: $data['decision'],
            reason: $data['reason'] ?? null,
        );
    }

    public function isAccepted(): bool
    {
        return $this->decision === 'ok';
    }

    public function isRejected(): bool
    {
        return $this->decision === 'no';
    }
}
