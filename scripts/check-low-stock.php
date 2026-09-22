<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Repository\MySqlDashboardRepository;

/**
 * JOB-01: a standalone script, deliberately decoupled from the web request
 * cycle — no Apache, no session, no HTTP involved. It reuses the exact same
 * aggregation query the Admin/Warehouse dashboards already use
 * (DashboardRepository::getLowStockProducts()), so this report can never
 * drift out of sync with what the app itself shows.
 *
 * Run manually via:
 *   docker compose exec app php scripts/check-low-stock.php
 *
 * Not wired up to an actual cron scheduler — the brief explicitly says that
 * isn't required, just that the task be separable from the web request
 * lifecycle and runnable on demand.
 *
 * Bab 12.2: this batch job is exactly the kind of work worth measuring -
 * wall time and peak memory are printed at the end as evidence, rather than
 * assumed to be fine.
 */

$startedAt = hrtime(true);

$dashboardRepository = new MySqlDashboardRepository();
$lowStockProducts = $dashboardRepository->getLowStockProducts();

echo 'Low Stock Report — ' . date('Y-m-d H:i:s') . "\n";
echo str_repeat('-', 60) . "\n";

if ($lowStockProducts === []) {
    echo "No products are currently below their reorder point.\n";
} else {
    printf("%-10s %-30s %8s %8s\n", 'SKU', 'Name', 'Stock', 'Reorder');
    echo str_repeat('-', 60) . "\n";

    foreach ($lowStockProducts as $product) {
        printf(
            "%-10s %-30s %8d %8d\n",
            $product['sku'],
            mb_strimwidth((string) $product['name'], 0, 30, ''),
            (int) $product['total_stock'],
            (int) $product['reorder_point'],
        );
    }

    echo str_repeat('-', 60) . "\n";
    echo sprintf("Total: %d product(s) below reorder point.\n", count($lowStockProducts));
}

$elapsedMs = (hrtime(true) - $startedAt) / 1e6;
$peakMemoryMb = memory_get_peak_usage(true) / 1048576;

echo "\n";
printf("Profile: %.2f ms, peak memory %.2f MB\n", $elapsedMs, $peakMemoryMb);
