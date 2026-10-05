<?php

namespace App\Controller;

use App\Service\DashboardService;
use App\Support\AuthGuard;

final class DashboardController extends BaseController
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
            $this->render('dashboard/admin', [
                'user' => $user,
                'totalInventoryValue' => $this->dashboardService->totalInventoryValue(),
                'lowStockProducts' => $this->dashboardService->lowStockProducts(),
                'poStatusCounts' => $this->dashboardService->purchaseOrderStatusCounts(),
                'soStatusCounts' => $this->dashboardService->salesOrderStatusCounts(),
            ]);
            return;
        }

        if ($user['role'] === 'Sales') {
            $this->render('dashboard/sales', [
                'user' => $user,
                'soStatusCounts' => $this->dashboardService->salesOrderStatusCounts((int) $user['id']),
            ]);
            return;
        }

        // WarehouseStaff
        $this->render('dashboard/warehouse', [
            'user' => $user,
            'poAwaitingReceipt' => $this->dashboardService->purchaseOrdersAwaitingReceipt(),
            'soAwaitingIssue' => $this->dashboardService->salesOrdersAwaitingIssue(),
            'lowStockProducts' => $this->dashboardService->lowStockProducts(),
        ]);
    }
}
