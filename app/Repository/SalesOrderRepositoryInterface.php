<?php

namespace App\Repository;

use App\Entity\SalesOrder;

interface SalesOrderRepositoryInterface
{
    /** @return SalesOrder[] (without items) */
    public function findAll(): array;

    /** Hydrated with its items. */
    public function findById(int $id): ?SalesOrder;

    public function create(int $customerId, int $warehouseId, string $orderDate, int $createdBy): SalesOrder;

    public function addItem(int $salesOrderId, int $productId, int $qty, float $sellPrice): void;

    public function updateStatus(int $id, string $status): void;

    public function approve(int $id, int $approvedBy): void;

    /**
     * @return array{items: SalesOrder[], total: int}
     */
    public function search(?string $q, ?string $status, string $sort, int $page, int $perPage, ?int $createdBy = null): array;
}
