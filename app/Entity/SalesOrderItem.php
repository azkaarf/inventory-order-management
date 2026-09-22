<?php

namespace App\Entity;

final class SalesOrderItem
{
    public function __construct(
        public readonly int $id,
        public readonly int $productId,
        public readonly string $productName,
        public readonly int $qty,
        public readonly float $sellPrice,
    ) {
    }
}
