<?php

namespace App\DTOs;

class UpdateProfileDTO
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $last_name = null,
        public readonly ?string $email = null,
        public readonly ?string $melicode = null,
        public readonly ?string $password = null,
        public readonly ?string $birth_date = null,
        public readonly ?string $mobile_number = null,
        public readonly ?string $phone_number = null,
        public readonly ?string $postal_code = null,
        public readonly ?string $city = null,
        public readonly ?string $region_id = null,
        public readonly ?string $region = null,
        public readonly ?string $home_address = null,
        public readonly ?string $work_address = null,
        public readonly ?string $card_number = null,
        public readonly ?string $sheba_number = null,
        public readonly mixed $profile_photo_path = null,
        public readonly bool $requires_phone_verification = false,
        public readonly bool $requires_email_verification = false,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            last_name: $data['last_name'] ?? null,
            email: $data['email'] ?? null,
            melicode: $data['melicode'] ?? null,
            password: $data['password'] ?? null,
            birth_date: $data['birth_date'] ?? null,
            mobile_number: $data['mobile_number'] ?? null,
            phone_number: $data['phone_number'] ?? null,
            postal_code: $data['postal_code'] ?? null,
            city: $data['city'] ?? null,
            region_id: $data['region_id'] ?? null,
            region: $data['region'] ?? null,
            home_address: $data['home_address'] ?? null,
            work_address: $data['work_address'] ?? null,
            card_number: $data['card_number'] ?? null,
            sheba_number: $data['sheba_number'] ?? null,
            profile_photo_path: $data['profile_photo_path'] ?? null,
        );
    }

    public function toArray(): array
    {
        $data = [];
        
        if ($this->name != null) {
            $data['name'] = $this->name;
        }
        
        if ($this->last_name != null) {
            $data['last_name'] = $this->last_name;
        }
        
        if ($this->email != null) {
            $data['email'] = $this->email;
        }
        
        if ($this->melicode != null) {
            $data['melicode'] = $this->melicode;
        }
        
        if ($this->password != null) {
            $data['password'] = bcrypt($this->password);
        }
        
        if ($this->birth_date != null) {
            $data['birth_date'] = $this->birth_date;
        }
        
        if ($this->mobile_number != null) {
            $data['mobile_number'] = $this->mobile_number;
        }
        
        if ($this->phone_number != null) {
            $data['phone_number'] = $this->phone_number;
        }
        
        if ($this->postal_code != null) {
            $data['postal_code'] = $this->postal_code;
        }
        
        if ($this->city != null) {
            $data['city'] = $this->city;
        }
        
        if ($this->region_id != null) {
            $data['region_id'] = $this->region_id;
        }
        if ($this->region != null) {
            $data['region'] = $this->region;
        }
        
        if ($this->home_address != null) {
            $data['home_address'] = $this->home_address;
        }
        
        if ($this->work_address != null) {
            $data['work_address'] = $this->work_address;
        }
        
        if ($this->card_number != null) {
            $data['card_number'] = $this->card_number;
        }
        
        if ($this->sheba_number != null) {
            $data['sheba_number'] = $this->sheba_number;
        }
        
        if ($this->profile_photo_path != null) {
            $data['profile_photo_path'] = $this->profile_photo_path;
        }
        
        return $data;
    }

    public function hasChanges(): bool
    {
        return $this->name != null 
            || $this->last_name != null 
            || $this->email != null 
            || $this->melicode != null 
            || $this->password != null
            || $this->birth_date != null
            || $this->mobile_number != null
            || $this->phone_number != null
            || $this->postal_code != null
            || $this->city != null
            || $this->region_id != null
            || $this->region != null
            || $this->home_address != null
            || $this->work_address != null
            || $this->card_number != null
            || $this->sheba_number != null
            || $this->profile_photo_path != null;
    }

    public function hasEmailChange(): bool
    {
        return $this->email != null;
    }

    public function hasPasswordChange(): bool
    {
        return $this->password != null;
    }
}