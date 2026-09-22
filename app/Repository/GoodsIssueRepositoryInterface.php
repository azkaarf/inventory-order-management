<?php

namespace App\Repository;

interface GoodsIssueRepositoryInterface
{
    /**
     * ARCH-02: locks each affected product_stock row (SELECT ... FOR UPDATE)
     * inside one transaction, and rejects the WHOLE operation if any single
     * item doesn't have enough stock (Sales Order has no "PartiallyFulfilled"
     * status, so this is all-or-nothing). Otherwise it decreases stock, writes
     * one Issue stock_ledger row per item, and marks the sales order Fulfilled.
     *
     * @param array<int, array{item_id:int, product_id:int, product_name:string, qty:int}> $items
     *
     * @throws \App\Support\InsufficientStockException
     */
    public function issue(int $salesOrderId, int $warehouseId, array $items, int $performedBy): void;
}
