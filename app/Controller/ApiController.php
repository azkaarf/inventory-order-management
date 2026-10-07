<?php

namespace App\Controller;

use App\Repository\ProductRepositoryInterface;
use App\Repository\StockRepositoryInterface;

final class ApiController
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly StockRepositoryInterface $stockRepository,
    ) {
    }

    public function productAvailability(string $sku): void
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthenticated.']);
            return;
        }

        $product = $this->productRepository->findBySku($sku);

        if ($product === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Product not found.']);
            return;
        }

        $breakdown = $this->stockRepository->getBreakdownForProduct($product->id);

        http_response_code(200);
        echo json_encode([
            'sku' => $product->sku,
            'name' => $product->name,
            'total' => array_sum(array_column($breakdown, 'quantity')),
            'per_warehouse' => array_map(fn (array $row) => [
                'warehouse_id' => $row['warehouse_id'],
                'warehouse_name' => $row['warehouse_name'],
                'quantity' => $row['quantity'],
            ], $breakdown),
        ]);
    }
}
