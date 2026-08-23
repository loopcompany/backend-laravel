<?php

namespace App\DTOs;

class WalletPaymentDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly int $orderId,
    ) {}

    public static function fromRequest(array $data, int $userId): self
    {
        return new self(
            userId: $userId,
            orderId: $data['orderId'],
        );
    }
}
