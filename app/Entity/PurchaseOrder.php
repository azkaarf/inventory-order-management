<?php

namespace App\Entity;

final class PurchaseOrder
{
    /**
     * @param PurchaseOrderItem[] $items
     */
    public function __construct(
        public readonly int $id,
        public readonly int $supplierId,
        public readonly string $supplierName,
        public readonly int $warehouseId,
        public readonly string $warehouseName,
        public readonly string $status,
        public readonly string $orderDate,
        public readonly int $createdBy,
        public readonly array $items,
    ) {
    }
}
