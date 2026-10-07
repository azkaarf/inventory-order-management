<?php

namespace App\Dto;

final class ProductData
{
    public function __construct(
        public readonly string $name,
        public readonly int $categoryId,
        public readonly string $unit,
        public readonly float $buyPrice,
        public readonly float $sellPrice,
        public readonly int $reorderPoint,
        public readonly ?string $imagePath,
    ) {
    }
}
