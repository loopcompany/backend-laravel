<?php

namespace App\DTOs;

class DeliveryReportDTO
{
    public function __construct(
        public readonly int $orderId,
        public readonly int $technicianId,
        public readonly string $name,
        public readonly string $melicode,
        public readonly string $productInfo,
        public readonly ?string $accessories = null,
        public readonly ?string $labelCode = null,
        public readonly ?string $appearanceDefect = null,
        public readonly ?string $userDescription = null,
        public readonly ?string $technicalDescription = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            orderId: $data['order_id'] ?? $data['orderId'],
            technicianId: $data['technician_id'] ?? $data['technicianId'],
            name: $data['name'],
            melicode: $data['melicode'] ?? $data['national_code'] ?? '',
            productInfo: $data['product_info'] ?? $data['productInfo'] ?? '',
            accessories: $data['accessories'] ?? null,
            labelCode: $data['label_code'] ?? $data['labelCode'] ?? null,
            appearanceDefect: $data['appearance_defect'] ?? $data['appearanceDefect'] ?? null,
            userDescription: $data['user_description'] ?? $data['userDescription'] ?? null,
            technicalDescription: $data['technical_description'] ?? $data['technicalDescription'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'technician_id' => $this->technicianId,
            'name' => $this->name,
            'melicode' => $this->melicode,
            'product_info' => $this->productInfo,
            'accessories' => $this->accessories,
            'label_code' => $this->labelCode,
            'appearance_defect' => $this->appearanceDefect,
            'user_description' => $this->userDescription,
            'technical_description' => $this->technicalDescription,
        ];
    }
}
