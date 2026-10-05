<?php

namespace App\Dto;

/**
 * Data produk yang bisa diisi/diubah lewat form.
 * SKU sengaja tidak termasuk karena hanya di-set saat create dan tidak bisa diubah.
 */
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
