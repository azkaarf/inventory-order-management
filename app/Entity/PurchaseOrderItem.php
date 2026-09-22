<?php

namespace App\Entity;

final class PurchaseOrderItem
{
    public function __construct(
        public readonly int $id,
        public readonly int $productId,
        public readonly string $productName,
        public readonly int $qtyOrdered,
        public readonly int $qtyReceived,
        public readonly float $buyPrice,
    ) {
    }

    public function qtyRemaining(): int
    {
        return $this->qtyOrdered - $this->qtyReceived;
    }
}
