<?php

namespace App\DTOs;

class TransferRequestDTO
{
    public function __construct(
        public readonly int $technician_id,
        public readonly string $type,
        public readonly string $description,
    ) {}

    /**
     * Create DTO from request data
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            technician_id: $data['technician_id'],
            type: $data['type'],
            description: $data['description'],
        );
    }

    /**
     * Convert DTO to array
     */
    public function toArray(): array
    {
        return [
            'technician_id' => $this->technician_id,
            'type' => $this->type,
            'description' => $this->description,
            'status' => 0, // Default status: pending
        ];
    }
}
