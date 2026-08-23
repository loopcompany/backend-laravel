<?php

namespace App\DTOs;

class ForgotPasswordDTO
{
    public function __construct(
        public readonly string $melicode,
        public readonly string $phone,
        public readonly string $email,
        public readonly ?string $hashApp = '',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            melicode: $data['melicode'],
            phone: $data['phone'],
            email: $data['email'],
            hashApp: $data['hashApp'] ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'melicode' => $this->melicode,
            'phone' => $this->phone,
            'email' => $this->email,
            'hashApp' => $this->hashApp ?? '',
        ];
    }
}
