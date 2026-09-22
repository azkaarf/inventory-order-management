<?php

namespace App\Service;

use App\Entity\Product;
use App\Repository\CategoryRepositoryInterface;
use App\Repository\ProductRepositoryInterface;
use App\Repository\StockRepositoryInterface;

final class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly StockRepositoryInterface $stockRepository,
    ) {
    }

    /** @return Product[] */
    public function listProducts(): array
    {
        return $this->productRepository->findAll();
    }

    /**
     * FIND-01: search by name/SKU, filter by category & stock status, 10/page.
     *
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, perPage: int, totalPages: int}
     */
    public function searchProducts(?string $q, ?int $categoryId, ?string $stockStatus, int $page): array
    {
        $perPage = 10;
        $page = max(1, $page);

        $result = $this->productRepository->search($q, $categoryId, $stockStatus, $page, $perPage);

        return [
            'items' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => max(1, (int) ceil($result['total'] / $perPage)),
        ];
    }

    public function findById(int $id): ?Product
    {
        return $this->productRepository->findById($id);
    }

    public function stockBreakdown(int $id): array
    {
        return $this->stockRepository->getBreakdownForProduct($id);
    }

    public function totalStock(int $id): int
    {
        return $this->stockRepository->getTotalForProduct($id);
    }

    /** @return string[] */
    public function validate(
        string $sku,
        string $name,
        int $categoryId,
        string $unit,
        string $buyPrice,
        string $sellPrice,
        string $reorderPoint,
        ?int $excludeId = null,
    ): array {
        $errors = [];

        if (trim($sku) === '') {
            $errors[] = 'SKU is required.';
        } elseif ($this->productRepository->skuExists($sku, $excludeId)) {
            $errors[] = 'SKU is already used by another product.';
        }

        if (trim($name) === '') {
            $errors[] = 'Product name is required.';
        }

        if ($categoryId <= 0 || $this->categoryRepository->findById($categoryId) === null) {
            $errors[] = 'Invalid category.';
        }

        if (trim($unit) === '') {
            $errors[] = 'Unit is required.';
        }

        if (!is_numeric($buyPrice) || (float) $buyPrice < 0) {
            $errors[] = 'Buy price must be a number and cannot be negative.';
        }

        if (!is_numeric($sellPrice) || (float) $sellPrice < 0) {
            $errors[] = 'Sell price must be a number and cannot be negative.';
        }

        if (!ctype_digit($reorderPoint) && $reorderPoint !== '0') {
            $errors[] = 'Reorder point must be a whole number and cannot be negative.';
        }

        return $errors;
    }

    public function createProduct(
        string $sku,
        string $name,
        int $categoryId,
        string $unit,
        float $buyPrice,
        float $sellPrice,
        int $reorderPoint,
        ?string $imagePath,
    ): Product {
        $product = $this->productRepository->create(
            $sku,
            $name,
            $categoryId,
            $unit,
            $buyPrice,
            $sellPrice,
            $reorderPoint,
            $imagePath,
        );

        // WH-01: a new product immediately gets a stock row (0) in every active warehouse
        $this->stockRepository->initializeForProduct($product->id);

        return $product;
    }

    public function updateProduct(
        int $id,
        string $name,
        int $categoryId,
        string $unit,
        float $buyPrice,
        float $sellPrice,
        int $reorderPoint,
        ?string $imagePath,
    ): void {
        $this->productRepository->update($id, $name, $categoryId, $unit, $buyPrice, $sellPrice, $reorderPoint, $imagePath);
    }

    public function setActive(int $id, bool $isActive): void
    {
        $this->productRepository->setActive($id, $isActive);
    }
}
