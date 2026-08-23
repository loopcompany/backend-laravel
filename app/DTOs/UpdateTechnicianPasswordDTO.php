<?php

namespace App\DTOs;

class UpdateTechnicianPasswordDTO
{
    public function __construct(
        public readonly string $current_password,
        public readonly string $new_password,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            current_password: $data['current_password'],
            new_password: $data['new_password'],
        );
    }
}
