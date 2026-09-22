<?php

namespace App\Repository;

use App\Support\Database;
use PDO;

final class MySqlStockRepository implements StockRepositoryInterface
{
    public function initializeForProduct(int $productId): void
    {
        $warehouseIds = Database::connection()
            ->query('SELECT id FROM warehouse WHERE is_active = 1')
            ->fetchAll(PDO::FETCH_COLUMN);

        $stmt = Database::connection()->prepare(
            'INSERT IGNORE INTO product_stock (product_id, warehouse_id, quantity) VALUES (?, ?, 0)'
        );

        foreach ($warehouseIds as $warehouseId) {
            $stmt->execute([$productId, $warehouseId]);
        }
    }

    public function getBreakdownForProduct(int $productId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT w.id AS warehouse_id, w.name AS warehouse_name, ps.quantity
             FROM product_stock ps
             JOIN warehouse w ON w.id = ps.warehouse_id
             WHERE ps.product_id = ?
             ORDER BY w.name'
        );
        $stmt->execute([$productId]);

        return $stmt->fetchAll();
    }

    public function getTotalForProduct(int $productId): int
    {
        $stmt = Database::connection()->prepare(
            'SELECT COALESCE(SUM(quantity), 0) FROM product_stock WHERE product_id = ?'
        );
        $stmt->execute([$productId]);

        return (int) $stmt->fetchColumn();
    }
}
