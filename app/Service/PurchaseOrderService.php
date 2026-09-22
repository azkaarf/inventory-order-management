<?php

namespace App\Service;

use App\Entity\PurchaseOrder;
use App\Entity\PurchaseOrderItem;
use App\Repository\GoodsReceiptRepositoryInterface;
use App\Repository\ProductRepositoryInterface;
use App\Repository\PurchaseOrderRepositoryInterface;
use App\Repository\SupplierRepositoryInterface;
use App\Repository\WarehouseRepositoryInterface;
use App\Support\DateValidator;

final class PurchaseOrderService
{
    public function __construct(
        private readonly PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly SupplierRepositoryInterface $supplierRepository,
        private readonly WarehouseRepositoryInterface $warehouseRepository,
        private readonly GoodsReceiptRepositoryInterface $goodsReceiptRepository,
    ) {
    }

    /** @return PurchaseOrder[] */
    public function listPurchaseOrders(): array
    {
        return $this->purchaseOrderRepository->findAll();
    }

    /**
     * FIND-01: search by PO number/supplier, filter by status, sort by date, 10/page.
     *
     * @return array{items: PurchaseOrder[], total: int, page: int, perPage: int, totalPages: int}
     */
    public function searchPurchaseOrders(?string $q, ?string $status, string $sort, int $page): array
    {
        $perPage = 10;
        $page = max(1, $page);

        $result = $this->purchaseOrderRepository->search($q, $status, $sort, $page, $perPage);

        return [
            'items' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => max(1, (int) ceil($result['total'] / $perPage)),
        ];
    }

    public function findById(int $id): ?PurchaseOrder
    {
        return $this->purchaseOrderRepository->findById($id);
    }

    /** @return string[] */
    public function validateHeader(int $supplierId, int $warehouseId, string $orderDate): array
    {
        $errors = [];

        if ($supplierId <= 0 || $this->supplierRepository->findById($supplierId) === null) {
            $errors[] = 'Invalid supplier.';
        }

        if ($warehouseId <= 0 || $this->warehouseRepository->findById($warehouseId) === null) {
            $errors[] = 'Invalid warehouse.';
        }

        if (!DateValidator::isValid($orderDate)) {
            $errors[] = 'Order date is invalid.';
        }

        return $errors;
    }

    public function createPurchaseOrder(int $supplierId, int $warehouseId, string $orderDate, int $createdBy): PurchaseOrder
    {
        return $this->purchaseOrderRepository->create($supplierId, $warehouseId, $orderDate, $createdBy);
    }

    /** @return string[] */
    public function validateItem(PurchaseOrder $po, int $productId, string $qty, string $buyPrice): array
    {
        $errors = [];

        if ($po->status !== 'Draft') {
            $errors[] = 'Items can only be added while the purchase order is still a Draft.';
        }

        if ($productId <= 0 || $this->productRepository->findById($productId) === null) {
            $errors[] = 'Invalid product.';
        }

        if (!ctype_digit($qty) || (int) $qty <= 0) {
            $errors[] = 'Quantity must be a positive whole number.';
        }

        if (!is_numeric($buyPrice) || (float) $buyPrice < 0) {
            $errors[] = 'Buy price must be a number and cannot be negative.';
        }

        return $errors;
    }

    public function addItem(int $purchaseOrderId, int $productId, int $qty, float $buyPrice): void
    {
        $this->purchaseOrderRepository->addItem($purchaseOrderId, $productId, $qty, $buyPrice);
    }

    /**
     * @return bool false if the PO isn't Draft, or has no items yet
     */
    public function markAsOrdered(int $id): bool
    {
        $po = $this->purchaseOrderRepository->findById($id);

        if ($po === null || $po->status !== 'Draft' || empty($po->items)) {
            return false;
        }

        $this->purchaseOrderRepository->updateStatus($id, 'Ordered');

        return true;
    }

    /**
     * @return bool false if the PO is already Received or Cancelled
     */
    public function cancel(int $id): bool
    {
        $po = $this->purchaseOrderRepository->findById($id);

        if ($po === null || in_array($po->status, ['Received', 'Cancelled'], true)) {
            return false;
        }

        $this->purchaseOrderRepository->updateStatus($id, 'Cancelled');

        return true;
    }

    /** @return string[] */
    public function validateReceipt(PurchaseOrder $po, PurchaseOrderItem $item, int $qtyToReceive): array
    {
        $errors = [];

        if (!in_array($po->status, ['Ordered', 'PartiallyReceived'], true)) {
            $errors[] = 'This purchase order is not in a receivable status.';
        }

        if ($qtyToReceive <= 0) {
            $errors[] = 'Quantity received must be greater than zero.';
        }

        if ($qtyToReceive > $item->qtyRemaining()) {
            $errors[] = 'Quantity received cannot exceed the remaining ordered quantity.';
        }

        return $errors;
    }

    public function receiveItem(PurchaseOrder $po, PurchaseOrderItem $item, int $qtyToReceive, int $performedBy): void
    {
        $this->goodsReceiptRepository->receive($po->id, $item->id, $item->productId, $po->warehouseId, $qtyToReceive, $performedBy);
    }
}
