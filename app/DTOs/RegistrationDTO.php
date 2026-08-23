<?php

namespace App\DTOs;

class RegistrationDTO
{
    public function __construct(
        public readonly string $melicode,
        public readonly string $phone,
        public readonly string $email,
        public readonly ?string $other_referral_code = null,
        public readonly ?int $province_id = null,
        public readonly ?int $city_id = null,
        public readonly ?int $region_id = null,
        public readonly ?string $hashApp = '',
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            melicode: $data['melicode'],
            phone: $data['phone'],
            email: $data['email'],
            other_referral_code: $data['other_referral_code'] ?? null,
            province_id: $data['province_id'] ?? null,
            city_id: $data['city_id'] ?? null,
            region_id: $data['region_id'] ?? null,
            hashApp: $data['hashApp'] ?? ''
        );
    }

    public function toArray(): array
    {
        return [
            'melicode' => $this->melicode,
            'phone' => $this->phone,
            'email' => $this->email,
            'other_referral_code' => $this->other_referral_code,
            'province_id' => $this->province_id,
            'city_id' => $this->city_id,
            'region_id' => $this->region_id,
            'hashApp' => $this->hashApp ?? '',
        ];
    }
}