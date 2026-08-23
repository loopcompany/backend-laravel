<?php

namespace App\DTOs;

class UpdateTechnicianVehicleInfoDTO
{
    public function __construct(
        public readonly ?string $vehicle_type,
        public readonly ?string $car_model,
        public readonly ?string $car_color,
        public readonly ?string $car_plate,
        public readonly ?string $car_year,
        public readonly ?string $car_fuel_type,
        public readonly ?string $car_vin,
        public readonly ?string $car_insurance_code,
        public readonly ?string $car_insurance_expiry_date,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            vehicle_type: $data['vehicle_type'] ?? null,
            car_model: $data['car_model'] ?? null,
            car_color: $data['car_color'] ?? null,
            car_plate: $data['car_plate'] ?? null,
            car_year: $data['car_year'] ?? null,
            car_fuel_type: $data['car_fuel_type'] ?? null,
            car_vin: $data['car_vin'] ?? null,
            car_insurance_code: $data['car_insurance_code'] ?? null,
            car_insurance_expiry_date: $data['car_insurance_expiry_date'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'vehicle_type' => $this->vehicle_type,
            'car_model' => $this->car_model,
            'car_color' => $this->car_color,
            'car_plate' => $this->car_plate,
            'car_year' => $this->car_year,
            'car_fuel_type' => $this->car_fuel_type,
            'car_vin' => $this->car_vin,
            'car_insurance_code' => $this->car_insurance_code,
            'car_insurance_expiry_date' => $this->car_insurance_expiry_date,
        ], fn($value) => $value != null);
    }
}
