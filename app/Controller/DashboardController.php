<?php

namespace App\Controller;

use App\Service\DashboardService;
use App\Support\AuthGuard;

final class DashboardController
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {
    }

    /**
     * DASH-01: every number here comes from an aggregation query, not a
     * static figure — and each role sees a different view.
     */
    public function show(): void
    {
        $user = AuthGuard::requireLogin();

        if ($user['role'] === 'Admin') {
            $totalInventoryValue = $this->dashboardService->totalInventoryValue();
            $lowStockProducts = $this->dashboardService->lowStockProducts();
            $poStatusCounts = $this->dashboardService->purchaseOrderStatusCounts();
            $soStatusCounts = $this->dashboardService->salesOrderStatusCounts();

            require __DIR__ . '/../../views/dashboard/admin.php';
            return;
        }

        if ($user['role'] === 'Sales') {
            $soStatusCounts = $this->dashboardService->salesOrderStatusCounts((int) $user['id']);

            require __DIR__ . '/../../views/dashboard/sales.php';
            return;
        }

        // WarehouseStaff
        $poAwaitingReceipt = $this->dashboardService->purchaseOrdersAwaitingReceipt();
        $soAwaitingIssue = $this->dashboardService->salesOrdersAwaitingIssue();
        $lowStockProducts = $this->dashboardService->lowStockProducts();

        require __DIR__ . '/../../views/dashboard/warehouse.php';
    }
}
