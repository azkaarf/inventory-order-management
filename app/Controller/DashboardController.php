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

        $this->render('dashboard/warehouse', [
            'user' => $user,
            'poAwaitingReceipt' => $this->dashboardService->purchaseOrdersAwaitingReceipt(),
            'soAwaitingIssue' => $this->dashboardService->salesOrdersAwaitingIssue(),
            'lowStockProducts' => $this->dashboardService->lowStockProducts(),
        ]);
    }
}
