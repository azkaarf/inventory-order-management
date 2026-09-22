<?php

namespace App\Repository;

use App\Entity\PurchaseOrder;

interface PurchaseOrderRepositoryInterface
{
    /** @return PurchaseOrder[] (without items, for the list view) */
    public function findAll(): array;

    /** Hydrated with its items. */
    public function findById(int $id): ?PurchaseOrder;

    public function create(int $supplierId, int $warehouseId, string $orderDate, int $createdBy): PurchaseOrder;

    public function addItem(int $purchaseOrderId, int $productId, int $qtyOrdered, float $buyPrice): void;

    public function updateStatus(int $id, string $status): void;

    /**
     * @return array{items: PurchaseOrder[], total: int}
     */
    public function search(?string $q, ?string $status, string $sort, int $page, int $perPage): array;
}
