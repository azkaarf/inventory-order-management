<?php

namespace App\Controller;

use App\Service\DashboardService;
use App\Support\AuthGuard;
use App\Support\CsvResponse;

/**
 * REPORT-01: CSV exports built from the exact same aggregation queries as
 * the dashboard (DashboardService), not a separate parallel query.
 */
final class ReportController extends BaseController
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {
    }

    public function index(): void
    {
        AuthGuard::requireRole(['Admin', 'Sales', 'WarehouseStaff']);

        $this->render('reports/index', [
            'from' => $_GET['from'] ?? date('Y-m-d', strtotime('-30 days')),
            'to' => $_GET['to'] ?? date('Y-m-d'),
            'error' => $_GET['error'] ?? null,
        ]);
    }

    public function stockLedgerCsv(): void
    {
        AuthGuard::requireRole(['Admin', 'WarehouseStaff']);

        [$from, $to, $redirect] = $this->resolveDateRange();
        if ($redirect !== null) {
            $this->redirect($redirect);
        }

        $rows = $this->dashboardService->stockLedgerReport($from, $to);

        CsvResponse::stream("stock-ledger_{$from}_to_{$to}.csv", function ($out) use ($rows) {
            fputcsv($out, ['Date', 'SKU', 'Product', 'Warehouse', 'Movement', 'Quantity', 'Reference', 'Performed By']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row['created_at'],
                    $row['sku'],
                    $row['product_name'],
                    $row['warehouse_name'],
                    $row['movement_type'],
                    $row['quantity'],
                    $row['reference_type'] . ' #' . $row['reference_id'],
                    $row['performed_by_name'],
                ]);
            }
        });
    }

    public function ordersCsv(): void
    {
        $user = AuthGuard::requireRole(['Admin', 'Sales']);

        [$from, $to, $redirect] = $this->resolveDateRange();
        if ($redirect !== null) {
            $this->redirect($redirect);
        }

        $createdBy = $user['role'] === 'Sales' ? (int) $user['id'] : null;
        $rows = $this->dashboardService->orderStatusReport($from, $to, $createdBy);

        CsvResponse::stream("orders_{$from}_to_{$to}.csv", function ($out) use ($rows) {
            fputcsv($out, ['Type', 'Order #', 'Party', 'Status', 'Order Date']);
            foreach ($rows as $row) {
                fputcsv($out, [$row['type'], $row['id'], $row['party_name'], $row['status'], $row['order_date']]);
            }
        });
    }

    /** @return array{0:string,1:string,2:?string} [from, to, redirectUrlOrNull] */
    private function resolveDateRange(): array
    {
        $from = $_GET['from'] ?? '';
        $to = $_GET['to'] ?? '';

        $errors = $this->dashboardService->validateDateRange($from, $to);

        if (!empty($errors)) {
            $redirect = '/reports?error=invalid-range&from=' . urlencode($from) . '&to=' . urlencode($to);

            return [$from, $to, $redirect];
        }

        return [$from, $to, null];
    }
}
