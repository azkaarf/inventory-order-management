<?php

namespace App\Entity;

final class SalesOrder
{
    /**
     * @param SalesOrderItem[] $items
     */
    public function __construct(
        public readonly int $id,
        public readonly int $customerId,
        public readonly string $customerName,
        public readonly int $warehouseId,
        public readonly string $warehouseName,
        public readonly string $status,
        public readonly int $createdBy,
        public readonly string $createdByName,
        public readonly ?int $approvedBy,
        public readonly ?string $approvedByName,
        public readonly string $orderDate,
        public readonly array $items,
    ) {
    }
}
