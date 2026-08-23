<?php

namespace App\DTOs;

class StoreExtraServicesDTO
{
    public function __construct(
        public readonly int $orderId,
        public readonly array $extras // [{id, extra_detail_id?, price?}, ...]
    ) {}

    /**
     * ایجاد DTO از آرایه
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            orderId: $data['order_id'],
            extras: $data['extras'] ?? []
        );
    }

    /**
     * تبدیل به آرایه
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'extras' => $this->extras,
        ];
    }
}
