<?php

namespace App\DTOs;

class TerminationRequestDTO
{
    public function __construct(
        public readonly int $technician_id,
        public readonly string $type,
        public readonly string $start_date,
        public readonly ?string $end_date,
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
            start_date: $data['start_date'],
            end_date: $data['end_date'] ?? null,
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
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'description' => $this->description,
            'status' => 0, // Default status: pending
        ];
    }
}
