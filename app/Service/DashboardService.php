<?php

namespace App\Service;

use App\Repository\DashboardRepositoryInterface;
use App\Support\DateValidator;

final class DashboardService
{
    public function __construct(
        private readonly DashboardRepositoryInterface $dashboardRepository,
    ) {
    }

    public function totalInventoryValue(): float
    {
        return $this->dashboardRepository->getTotalInventoryValue();
    }

    /** @return array<int, array<string, mixed>> */
    public function lowStockProducts(): array
    {
        return $this->dashboardRepository->getLowStockProducts();
    }

    /** @return array<string, int> */
    public function purchaseOrderStatusCounts(): array
    {
        return $this->dashboardRepository->getPurchaseOrderStatusCounts();
    }

    /** @return array<string, int> */
    public function salesOrderStatusCounts(?int $createdBy = null): array
    {
        return $this->dashboardRepository->getSalesOrderStatusCounts($createdBy);
    }

    /** @return array<int, array<string, mixed>> */
    public function purchaseOrdersAwaitingReceipt(): array
    {
        return $this->dashboardRepository->getPurchaseOrdersAwaitingReceipt();
    }

    /** @return array<int, array<string, mixed>> */
    public function salesOrdersAwaitingIssue(): array
    {
        return $this->dashboardRepository->getSalesOrdersAwaitingIssue();
    }

    /** @return array<int, array<string, mixed>> */
    public function stockLedgerReport(string $fromDate, string $toDate): array
    {
        return $this->dashboardRepository->getStockLedgerReport($fromDate, $toDate);
    }

    /** @return array<int, array<string, mixed>> */
    public function orderStatusReport(string $fromDate, string $toDate, ?int $createdBy = null): array
    {
        return $this->dashboardRepository->getOrderStatusReport($fromDate, $toDate, $createdBy);
    }

    /**
     * REPORT-01: validate the date range picked on the Reports page, before
     * it's used for either CSV export.
     *
     * @return string[]
     */
    public function validateDateRange(string $fromDate, string $toDate): array
    {
        $errors = [];

        if (!DateValidator::isValid($fromDate)) {
            $errors[] = 'The "from" date is invalid.';
        }

        if (!DateValidator::isValid($toDate)) {
            $errors[] = 'The "to" date is invalid.';
        }

        if (empty($errors) && $fromDate > $toDate) {
            $errors[] = 'The "from" date must not be after the "to" date.';
        }

        return $errors;
    }
}
