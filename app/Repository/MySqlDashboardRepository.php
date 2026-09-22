<?php

namespace App\Repository;

use App\Support\Database;

final class MySqlDashboardRepository implements DashboardRepositoryInterface
{
    public function getTotalInventoryValue(): float
    {
        $stmt = Database::connection()->query(
            'SELECT COALESCE(SUM(p.buy_price * ps.quantity), 0)
             FROM product_stock ps
             JOIN product p ON p.id = ps.product_id
             WHERE p.is_active = 1'
        );

        return (float) $stmt->fetchColumn();
    }

    public function getLowStockProducts(): array
    {
        $stmt = Database::connection()->query(
            'SELECT p.id, p.sku, p.name, COALESCE(SUM(ps.quantity), 0) AS total_stock, p.reorder_point
             FROM product p
             LEFT JOIN product_stock ps ON ps.product_id = p.id
             WHERE p.is_active = 1
             GROUP BY p.id
             HAVING total_stock < p.reorder_point
             ORDER BY p.name'
        );

        return $stmt->fetchAll();
    }

    public function getPurchaseOrderStatusCounts(): array
    {
        $stmt = Database::connection()->query(
            'SELECT status, COUNT(*) AS cnt FROM purchase_order GROUP BY status'
        );
        $rows = $stmt->fetchAll();

        return array_combine(
            array_column($rows, 'status'),
            array_map('intval', array_column($rows, 'cnt')),
        );
    }

    public function getSalesOrderStatusCounts(?int $createdBy = null): array
    {
        if ($createdBy === null) {
            $stmt = Database::connection()->query(
                'SELECT status, COUNT(*) AS cnt FROM sales_order GROUP BY status'
            );
            $rows = $stmt->fetchAll();
        } else {
            $stmt = Database::connection()->prepare(
                'SELECT status, COUNT(*) AS cnt FROM sales_order WHERE created_by = ? GROUP BY status'
            );
            $stmt->execute([$createdBy]);
            $rows = $stmt->fetchAll();
        }

        $counts = array_combine(
            array_column($rows, 'status'),
            array_map('intval', array_column($rows, 'cnt')),
        );

        return $counts;
    }

    public function getPurchaseOrdersAwaitingReceipt(): array
    {
        $stmt = Database::connection()->query(
            "SELECT po.id, s.name AS supplier_name, po.status, po.order_date
             FROM purchase_order po
             JOIN supplier s ON s.id = po.supplier_id
             WHERE po.status IN ('Ordered', 'PartiallyReceived')
             ORDER BY po.order_date"
        );

        return $stmt->fetchAll();
    }

    public function getSalesOrdersAwaitingIssue(): array
    {
        $stmt = Database::connection()->query(
            "SELECT so.id, c.name AS customer_name, so.status, so.order_date
             FROM sales_order so
             JOIN customer c ON c.id = so.customer_id
             WHERE so.status = 'Approved'
             ORDER BY so.order_date"
        );

        return $stmt->fetchAll();
    }

    public function getStockLedgerReport(string $fromDate, string $toDate): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT sl.created_at, p.sku, p.name AS product_name, w.name AS warehouse_name,
                    sl.movement_type, sl.quantity, sl.reference_type, sl.reference_id, u.name AS performed_by_name
             FROM stock_ledger sl
             JOIN product p ON p.id = sl.product_id
             JOIN warehouse w ON w.id = sl.warehouse_id
             JOIN user u ON u.id = sl.performed_by
             WHERE DATE(sl.created_at) BETWEEN ? AND ?
             ORDER BY sl.created_at'
        );
        $stmt->execute([$fromDate, $toDate]);

        return $stmt->fetchAll();
    }

    public function getOrderStatusReport(string $fromDate, string $toDate, ?int $createdBy = null): array
    {
        if ($createdBy !== null) {
            $stmt = Database::connection()->prepare(
                "SELECT 'SO' AS type, so.id, c.name AS party_name, so.status, so.order_date
                 FROM sales_order so
                 JOIN customer c ON c.id = so.customer_id
                 WHERE so.order_date BETWEEN ? AND ? AND so.created_by = ?
                 ORDER BY so.order_date"
            );
            $stmt->execute([$fromDate, $toDate, $createdBy]);

            return $stmt->fetchAll();
        }

        $stmt = Database::connection()->prepare(
            "SELECT 'PO' AS type, po.id, s.name AS party_name, po.status, po.order_date
             FROM purchase_order po
             JOIN supplier s ON s.id = po.supplier_id
             WHERE po.order_date BETWEEN ? AND ?
             UNION ALL
             SELECT 'SO' AS type, so.id, c.name AS party_name, so.status, so.order_date
             FROM sales_order so
             JOIN customer c ON c.id = so.customer_id
             WHERE so.order_date BETWEEN ? AND ?
             ORDER BY order_date"
        );
        $stmt->execute([$fromDate, $toDate, $fromDate, $toDate]);

        return $stmt->fetchAll();
    }
}
