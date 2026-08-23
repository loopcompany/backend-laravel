<?php

namespace App\DTOs;

use Illuminate\Http\UploadedFile;

class OrganizationRegistrationDTO
{
    public function __construct(
        // Organization Information
        public readonly string $account_type,
        public readonly string $organization_name,
        public readonly string $agent_name,
        public readonly string $agent_phone,
        public readonly ?string $business_name,
        public readonly string $history,
        public readonly string $organization_email,
        public readonly string $organization_phone,
        public readonly string $organization_address,
        
        // Manager Information
        public readonly string $manager_full_name,
        public readonly string $manager_national_code,
        public readonly string $melicode,
        public readonly string $manager_mobile,
        public readonly string $manager_birthdate,
        
        // Location
        public readonly ?string $city = null,
        public readonly ?string $hashApp = '',
        public readonly ?string $region = null,
        public readonly string $postal_code,
        public readonly ?int $province_id = null,
        public readonly ?int $city_id = null,
        public readonly ?int $region_id = null,
        
        // Authentication
        public readonly string $password,
        
        // Optional Profile Image
        public readonly ?UploadedFile $profile_image = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            account_type: $data['account_type'],
            organization_name: $data['organization_name'],
            agent_name: $data['agent_name'],
            agent_phone: $data['agent_phone'],
            business_name: $data['business_name'],
            history: $data['history'],
            organization_email: $data['organization_email'],
            organization_phone: $data['organization_phone'],
            organization_address: $data['organization_address'],
            manager_full_name: $data['manager_full_name'],
            manager_national_code: $data['manager_national_code'],
            melicode: $data['melicode'],
            manager_mobile: $data['manager_mobile'],
            manager_birthdate: $data['manager_birthdate'],
            city: $data['city'] ?? null,
            region: $data['region'] ?? null,
            postal_code: $data['postal_code'],
            province_id: $data['province_id'] ?? null,
            city_id: $data['city_id'] ?? null,
            region_id: $data['region_id'] ?? null,
            password: $data['password'],
            profile_image: $data['profile_image'] ?? null,
            hashApp: $data['hashApp'] ?? ''
        );
    }

    public function toArray(): array
    {
        return [
            'organization_name' => $this->organization_name,
            'agent_name' => $this->agent_name,
            'agent_phone' => $this->agent_phone,
            'business_name' => $this->business_name,
            'history' => $this->history,
            'organization_email' => $this->organization_email,
            'organization_phone' => $this->organization_phone,
            'organization_address' => $this->organization_address,
            'manager_full_name' => $this->manager_full_name,
            'manager_national_code' => $this->manager_national_code,
            'melicode' => $this->melicode,
            'manager_mobile' => $this->manager_mobile,
            'manager_birthdate' => $this->manager_birthdate,
            'city' => $this->city,
            'region' => $this->region,
            'postal_code' => $this->postal_code,
            'province_id' => $this->province_id,
            'city_id' => $this->city_id,
            'region_id' => $this->region_id,
            'password' => $this->password,
        ];
    }
}
