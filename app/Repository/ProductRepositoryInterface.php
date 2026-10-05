<?php

namespace App\Repository;

use App\Dto\ProductData;
use App\Entity\Product;

interface ProductRepositoryInterface
{
    /** @return Product[] */
    public function findAll(): array;

    public function findById(int $id): ?Product;

    public function findBySku(string $sku): ?Product;

    /**
     * @return array{items: array<int, array<string, mixed>>, total: int}
     */
    public function search(?string $q, ?int $categoryId, ?string $stockStatus, int $page, int $perPage): array;

    public function skuExists(string $sku, ?int $excludeId = null): bool;

    public function create(string $sku, ProductData $data): Product;

    public function update(int $id, ProductData $data): void;

    public function setActive(int $id, bool $isActive): void;
}
