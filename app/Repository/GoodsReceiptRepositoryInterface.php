<?php

namespace App\Repository;

interface GoodsReceiptRepositoryInterface
{
    /**
     * PO-01 / DB-01: writes the stock_ledger row (Receipt), increases
     * product_stock, updates the purchase order item's qty_received, and
     * recomputes the purchase order's overall status (PartiallyReceived vs
     * Received) — all inside a single explicit transaction.
     */
    public function receive(
        int $purchaseOrderId,
        int $itemId,
        int $productId,
        int $warehouseId,
        int $qtyReceived,
        int $performedBy,
    ): void;
}
