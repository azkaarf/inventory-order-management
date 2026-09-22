<?php

namespace App\Repository;

interface DashboardRepositoryInterface
{
    public function getTotalInventoryValue(): float;

    /** @return array<int, array<string, mixed>> */
    public function getLowStockProducts(): array;

    /** @return array<string, int> status => count */
    public function getPurchaseOrderStatusCounts(): array;

    /** @return array<string, int> status => count */
    public function getSalesOrderStatusCounts(?int $createdBy = null): array;

    /** @return array<int, array<string, mixed>> */
    public function getPurchaseOrdersAwaitingReceipt(): array;

    /** @return array<int, array<string, mixed>> */
    public function getSalesOrdersAwaitingIssue(): array;

    /**
     * REPORT-01: stock_ledger movements within a date range.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getStockLedgerReport(string $fromDate, string $toDate): array;

    /**
     * REPORT-01: combined PO+SO status snapshot within a date range.
     * $createdBy, when given, restricts to that user's own Sales Orders only
     * (and excludes Purchase Orders entirely, since Sales has no PO access).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getOrderStatusReport(string $fromDate, string $toDate, ?int $createdBy = null): array;
}
