<?php

namespace App\DTOs;

class UpdateTechnicianPersonalInfoDTO
{
    public function __construct(
        public readonly ?string $profile_photo_path,
        public readonly ?string $melicode,
        public readonly ?string $birth_date,
        public readonly ?string $father_name,
        public readonly ?string $issued_from,
        public readonly ?string $serial_number,
        public readonly ?string $marital_status,
        public readonly ?string $education_status,
        public readonly ?string $education_field,
        public readonly ?string $telephone,
        public readonly ?string $email,
        public readonly ?string $certificate_number,
        public readonly ?string $licence_date,
        public readonly ?string $certificate_issue_date,
        public readonly ?string $city,
        public readonly ?string $region,
        public readonly ?string $home_address,
        public readonly ?string $home_postal_code,
        public readonly ?string $technician_type,
        public readonly ?string $other_referral_code,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            profile_photo_path: $data['profile_photo_path'] ?? null,
            melicode: $data['melicode'] ?? null,
            birth_date: $data['birth_date'] ?? null,
            father_name: $data['father_name'] ?? null,
            issued_from: $data['issued_from'] ?? null,
            serial_number: $data['serial_number'] ?? null,
            marital_status: $data['marital_status'] ?? null,
            education_status: $data['education_status'] ?? null,
            education_field: $data['education_field'] ?? null,
            telephone: $data['telephone'] ?? null,
            email: $data['email'] ?? null,
            certificate_number: $data['certificate_number'] ?? null,
            licence_date: $data['licence_date'] ?? null,
            certificate_issue_date: $data['certificate_issue_date'] ?? null,
            city: $data['city'] ?? null,
            region: $data['region'] ?? null,
            home_address: $data['home_address'] ?? null,
            home_postal_code: $data['home_postal_code'] ?? null,
            technician_type: $data['technician_type'] ?? null,
            other_referral_code: $data['other_referral_code'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'profile_photo_path' => $this->profile_photo_path,
            'melicode' => $this->melicode,
            'birth_date' => $this->birth_date,
            'father_name' => $this->father_name,
            'issued_from' => $this->issued_from,
            'serial_number' => $this->serial_number,
            'marital_status' => $this->marital_status,
            'education_status' => $this->education_status,
            'education_field' => $this->education_field,
            'telephone' => $this->telephone,
            'email' => $this->email,
            'certificate_number' => $this->certificate_number,
            'licence_date' => $this->licence_date,
            'certificate_issue_date' => $this->certificate_issue_date,
            'city' => $this->city,
            'region' => $this->region,
            'home_address' => $this->home_address,
            'home_postal_code' => $this->home_postal_code,
            'technician_type' => $this->technician_type,
            'other_referral_code' => $this->other_referral_code,
        ], fn($value) => $value != null);
    }
}
