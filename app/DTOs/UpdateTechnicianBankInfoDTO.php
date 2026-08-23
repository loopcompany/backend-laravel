<?php

namespace App\DTOs;

class UpdateTechnicianBankInfoDTO
{
    public function __construct(
        public readonly ?string $bank_shaba_number,
        public readonly ?string $bank_name,
        public readonly ?string $bank_card_number,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            bank_shaba_number: $data['bank_shaba_number'] ?? null,
            bank_name: $data['bank_name'] ?? null,
            bank_card_number: $data['bank_card_number'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'bank_shaba_number' => $this->bank_shaba_number,
            'bank_name' => $this->bank_name,
            'bank_card_number' => $this->bank_card_number,
        ], fn($value) => $value != null);
    }
}
