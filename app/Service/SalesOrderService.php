<?php

namespace App\Service;

use App\Entity\SalesOrder;
use App\Entity\SalesOrderItem;
use App\Repository\CustomerRepositoryInterface;
use App\Repository\GoodsIssueRepositoryInterface;
use App\Repository\ProductRepositoryInterface;
use App\Repository\SalesOrderRepositoryInterface;
use App\Repository\WarehouseRepositoryInterface;
use App\Support\DateValidator;
use App\Support\InsufficientStockException;

final class SalesOrderService
{
    public function __construct(
        private readonly SalesOrderRepositoryInterface $salesOrderRepository,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly WarehouseRepositoryInterface $warehouseRepository,
        private readonly GoodsIssueRepositoryInterface $goodsIssueRepository,
    ) {
    }

    /** @return SalesOrder[] */
    public function listAll(): array
    {
        return $this->salesOrderRepository->findAll();
    }

    /**
     * FIND-01: search by SO number/customer, filter by status, sort by date, 10/page.
     * $createdBy restricts results to one owner (used for the Sales role).
     *
     * @return array{items: SalesOrder[], total: int, page: int, perPage: int, totalPages: int}
     */
    public function searchSalesOrders(?string $q, ?string $status, string $sort, int $page, ?int $createdBy = null): array
    {
        $perPage = 10;
        $page = max(1, $page);

        $result = $this->salesOrderRepository->search($q, $status, $sort, $page, $perPage, $createdBy);

        return [
            'items' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => max(1, (int) ceil($result['total'] / $perPage)),
        ];
    }

    public function findById(int $id): ?SalesOrder
    {
        return $this->salesOrderRepository->findById($id);
    }

    /** @return string[] */
    public function validateHeader(int $customerId, int $warehouseId, string $orderDate): array
    {
        $errors = [];

        if ($customerId <= 0 || $this->customerRepository->findById($customerId) === null) {
            $errors[] = 'Invalid customer.';
        }

        if ($warehouseId <= 0 || $this->warehouseRepository->findById($warehouseId) === null) {
            $errors[] = 'Invalid warehouse.';
        }

        if (!DateValidator::isValid($orderDate)) {
            $errors[] = 'Order date is invalid.';
        }

        return $errors;
    }

    public function createSalesOrder(int $customerId, int $warehouseId, string $orderDate, int $createdBy): SalesOrder
    {
        return $this->salesOrderRepository->create($customerId, $warehouseId, $orderDate, $createdBy);
    }

    /** @return string[] */
    public function validateItem(SalesOrder $so, int $productId, string $qty): array
    {
        $errors = [];

        if ($so->status !== 'Draft') {
            $errors[] = 'Items can only be added while the sales order is still a Draft.';
        }

        if ($productId <= 0 || $this->productRepository->findById($productId) === null) {
            $errors[] = 'Invalid product.';
        }

        if (!ctype_digit($qty) || (int) $qty <= 0) {
            $errors[] = 'Quantity must be a positive whole number.';
        }

        return $errors;
    }

    public function addItem(int $salesOrderId, int $productId, int $qty, float $sellPrice): void
    {
        $this->salesOrderRepository->addItem($salesOrderId, $productId, $qty, $sellPrice);
    }

    /** @return bool false if the SO isn't Draft, or has no items yet */
    public function submitForApproval(int $id): bool
    {
        $so = $this->salesOrderRepository->findById($id);

        if ($so === null || $so->status !== 'Draft' || empty($so->items)) {
            return false;
        }

        $this->salesOrderRepository->updateStatus($id, 'PendingApproval');

        return true;
    }

    /**
     * SO-01: the creator can never approve their own order — enforced here as
     * a second line of defense, on top of the role guard in the Controller
     * (which already blocks the Sales role from approving anything at all).
     *
     * @return bool false if not pending, or the approver is the creator
     */
    public function approve(int $id, int $approverId): bool
    {
        $so = $this->salesOrderRepository->findById($id);

        if ($so === null || $so->status !== 'PendingApproval' || $so->createdBy === $approverId) {
            return false;
        }

        $this->salesOrderRepository->approve($id, $approverId);

        return true;
    }

    public function reject(int $id): bool
    {
        $so = $this->salesOrderRepository->findById($id);

        if ($so === null || $so->status !== 'PendingApproval') {
            return false;
        }

        $this->salesOrderRepository->updateStatus($id, 'Cancelled');

        return true;
    }

    /** @return bool false if already Fulfilled or Cancelled */
    public function cancel(int $id): bool
    {
        $so = $this->salesOrderRepository->findById($id);

        if ($so === null || in_array($so->status, ['Fulfilled', 'Cancelled'], true)) {
            return false;
        }

        $this->salesOrderRepository->updateStatus($id, 'Cancelled');

        return true;
    }

    /** @return string[] */
    public function validateGoodsIssue(SalesOrder $so): array
    {
        $errors = [];

        if ($so->status !== 'Approved') {
            $errors[] = 'Goods issue can only be processed for an Approved sales order.';
        }

        return $errors;
    }

    /**
     * @throws InsufficientStockException
     */
    public function processGoodsIssue(SalesOrder $so, int $performedBy): void
    {
        $items = array_map(fn (SalesOrderItem $item) => [
            'item_id' => $item->id,
            'product_id' => $item->productId,
            'product_name' => $item->productName,
            'qty' => $item->qty,
        ], $so->items);

        $this->goodsIssueRepository->issue($so->id, $so->warehouseId, $items, $performedBy);
    }
}
