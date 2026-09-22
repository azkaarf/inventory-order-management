<?php

namespace App\Repository;

interface StockRepositoryInterface
{
    /**
     * WH-01: a new product immediately gets a stock row (0) in every active warehouse.
     */
    public function initializeForProduct(int $productId): void;

    /** @return array<int, array{warehouse_id:int, warehouse_name:string, quantity:int}> */
    public function getBreakdownForProduct(int $productId): array;

    public function getTotalForProduct(int $productId): int;
}
