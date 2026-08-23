<?php

namespace App\DTOs;

class SetTechnicianDescriptionDTO
{
    public function __construct(
        public readonly string $technician_des,
        public readonly string $date,
        public readonly string $time,
        public readonly int $technician_price,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            technician_des: $data['technician_des'],
            date: $data['date'],
            time: $data['time'],
            technician_price: $data['technician_price'],
        );
    }

    public function toArray(): array
    {
        return [
            'technician_des' => $this->technician_des,
            'date' => $this->date,
            'time' => $this->time,
            'technician_price' => $this->technician_price,
        ];
    }

    /**
     * ترکیب تاریخ و زمان به فرمت datetime
     */
    public function getDateTime(): string
    {
        return $this->date . ' ' . $this->time;
    }
}
