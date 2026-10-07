<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Repository\MySqlDashboardRepository;

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
