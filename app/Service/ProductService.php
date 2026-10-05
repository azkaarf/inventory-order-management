<?php

namespace App\Service;

use App\Dto\ProductData;
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

    /**
     * REPORT-01 pattern applied to Products: same filters, same Repository
     * query as searchProducts(), just without the 10/page cap - so the CSV
     * always matches exactly what search/filter would show onscreen.
     *
     * @return array<int, array<string, mixed>>
     */
    public function exportProducts(?string $q, ?int $categoryId, ?string $stockStatus): array
    {
        $result = $this->productRepository->search($q, $categoryId, $stockStatus, 1, 10000);

        return $result['items'];
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

    /**
     * Validasi input mentah dari form produk.
     *
     * @param array<string,string> $form keys: sku, name, category_id, unit, buy_price, sell_price, reorder_point
     * @return string[]
     */
    public function validate(array $form, ?int $excludeId = null): array
    {
        $sku = trim($form['sku'] ?? '');
        $name = trim($form['name'] ?? '');
        $categoryId = (int) ($form['category_id'] ?? 0);
        $unit = trim($form['unit'] ?? '');
        $buyPrice = $form['buy_price'] ?? '';
        $sellPrice = $form['sell_price'] ?? '';
        $reorderPoint = $form['reorder_point'] ?? '';

        $errors = [];

        if ($sku === '') {
            $errors[] = 'SKU is required.';
        } elseif ($this->productRepository->skuExists($sku, $excludeId)) {
            $errors[] = 'SKU is already used by another product.';
        }

        if ($name === '') {
            $errors[] = 'Product name is required.';
        }

        if ($categoryId <= 0 || $this->categoryRepository->findById($categoryId) === null) {
            $errors[] = 'Invalid category.';
        }

        if ($unit === '') {
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

    public function createProduct(string $sku, ProductData $data): Product
    {
        $product = $this->productRepository->create($sku, $data);

        // WH-01: a new product immediately gets a stock row (0) in every active warehouse
        $this->stockRepository->initializeForProduct($product->id);

        return $product;
    }

    public function updateProduct(int $id, ProductData $data): void
    {
        $this->productRepository->update($id, $data);
    }

    public function setActive(int $id, bool $isActive): void
    {
        $this->productRepository->setActive($id, $isActive);
    }
}
