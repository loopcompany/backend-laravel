<?php

namespace App\DTOs;

class PollApplicationDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly string $appRate,
        public readonly string $techRate,
        public readonly string $supportRate,
        public readonly ?string $description = null
    ) {}

    public static function fromArray(array $data, int $userId): self
    {
        return new self(
            userId: $userId,
            appRate: $data['app_rate'],
            techRate: $data['tech_rate'],
            supportRate: $data['support_rate'],
            description: $data['description'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'app_rate' => $this->appRate,
            'tech_rate' => $this->techRate,
            'support_rate' => $this->supportRate,
            'description' => $this->description,
        ];
    }
}