<?php

namespace App\DTOs;

class ProductDTO
{
    public function __construct(
        public ?int $id = null,
        public ?string $sku = null,
        public string $name,
        public ?string $description = null,
        public float $price,
        public ?string $category = null,
        public string $status = 'active',
        public ?string $imageUrl = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            sku: $data['sku'] ?? null,
            name: $data['name'],
            description: $data['description'] ?? null,
            price: (float) $data['price'],
            category: $data['category'] ?? null,
            status: $data['status'] ?? 'active',
            imageUrl: $data['image_url'] ?? $data['imageUrl'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'category' => $this->category,
            'status' => $this->status,
            'image_url' => $this->imageUrl,
        ];
    }
}
