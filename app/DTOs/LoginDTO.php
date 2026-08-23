<?php

namespace App\DTOs;

class LoginDTO
{
    public function __construct(
        public readonly string $phone,
        public readonly string $password
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            phone: $data['phone'],
            password: $data['password']
        );
    }

    public function toArray(): array
    {
        return [
            'phone' => $this->phone,
            'password' => $this->password,
        ];
    }
}