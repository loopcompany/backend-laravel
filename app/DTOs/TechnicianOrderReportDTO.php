<?php

namespace App\DTOs;

class TechnicianOrderReportDTO
{
    public function __construct(
        public readonly int $order_id,
        public readonly string $name,
        public readonly string $melicode,
        public readonly ?string $product_name = null,
        public readonly ?string $product_brand = null,
        public readonly ?string $product_model = null,
        public readonly ?string $product_color = null,
        public readonly ?string $product_serial_number = null,
        public readonly ?string $asset_label_code = null,
        public readonly ?string $accessories = null,
        public readonly ?string $max_price = null,
        public readonly ?string $min_price = null,
        public readonly ?string $product_password = null,
        public readonly ?string $user_reported_issues = null,
        public readonly ?string $technician_reported_issues = null,
        public readonly ?string $technician_observed_issues = null,
        public readonly ?string $user_requested_services = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            order_id: $data['order_id'],
            name: $data['name'],
            melicode: $data['melicode'],
            product_name: $data['product_name'] ?? null,
            product_brand: $data['product_brand'] ?? null,
            product_model: $data['product_model'] ?? null,
            product_color: $data['product_color'] ?? null,
            product_serial_number: $data['product_serial_number'] ?? null,
            asset_label_code: $data['asset_label_code'] ?? null,
            accessories: $data['accessories'] ?? null,
            max_price: $data['max_price'] ?? null,
            min_price: $data['min_price'] ?? null,
            product_password: $data['product_password'] ?? null,
            user_reported_issues: $data['user_reported_issues'] ?? null,
            technician_reported_issues: $data['technician_reported_issues'] ?? null,
            technician_observed_issues: $data['technician_observed_issues'] ?? null,
            user_requested_services: $data['user_requested_services'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'order_id' => $this->order_id,
            'name' => $this->name,
            'melicode' => $this->melicode,
            'product_name' => $this->product_name,
            'product_brand' => $this->product_brand,
            'product_model' => $this->product_model,
            'product_color' => $this->product_color,
            'product_serial_number' => $this->product_serial_number,
            'asset_label_code' => $this->asset_label_code,
            'accessories' => $this->accessories,
            'max_price' => $this->max_price,
            'min_price' => $this->min_price,
            'product_password' => $this->product_password,
            'user_reported_issues' => $this->user_reported_issues,
            'technician_reported_issues' => $this->technician_reported_issues,
            'technician_observed_issues' => $this->technician_observed_issues,
            'user_requested_services' => $this->user_requested_services,
        ];
    }
}
