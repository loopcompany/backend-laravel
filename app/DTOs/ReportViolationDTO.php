<?php

namespace App\DTOs;

class ReportViolationDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly ?string $subject,
        public readonly ?string $name,
        public readonly string $date,
        public readonly string $amount,
        public readonly string $description
    ) {}

    public static function fromArray(array $data, int $userId): self
    {
        return new self(
            userId: $userId,
            subject: $data['subject'] ?? null,
            name: $data['name'] ?? null,
            date: $data['date'],
            amount: $data['amount'],
            description: $data['description']
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'subject' => $this->subject,
            'name' => $this->name,
            'date' => $this->date,
            'amount' => $this->amount,
            'description' => $this->description,
        ];
    }
}