<?php

namespace App\Repository;

use App\Entity\SalesOrder;
use App\Entity\SalesOrderItem;
use App\Support\Database;

final class MySqlSalesOrderRepository implements SalesOrderRepositoryInterface
{
    // Nowdoc (bukan heredoc): isinya murni SQL literal, nggak ada variabel
    // PHP yang perlu di-interpolate, jadi 'SQL' (quoted) dipakai supaya PHP
    // nggak buang waktu mengecek interpolasi yang memang tidak dibutuhkan.
    private const BASE_SELECT = <<<'SQL'
        SELECT so.id, so.customer_id, c.name AS customer_name, so.warehouse_id, w.name AS warehouse_name,
               so.status, so.created_by, creator.name AS created_by_name,
               so.approved_by, approver.name AS approved_by_name, so.order_date
        FROM sales_order so
        JOIN customer c ON c.id = so.customer_id
        JOIN warehouse w ON w.id = so.warehouse_id
        JOIN user creator ON creator.id = so.created_by
        LEFT JOIN user approver ON approver.id = so.approved_by
        SQL;

    public function findAll(): array
    {
        $stmt = Database::connection()->query(self::BASE_SELECT . ' ORDER BY so.order_date DESC, so.id DESC');

        return array_map(fn (array $row) => $this->toEntity($row, []), $stmt->fetchAll());
    }

    public function findById(int $id): ?SalesOrder
    {
        $stmt = Database::connection()->prepare(self::BASE_SELECT . ' WHERE so.id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        $itemStmt = Database::connection()->prepare(
            'SELECT soi.id, soi.product_id, p.name AS product_name, soi.qty, soi.sell_price
             FROM sales_order_item soi
             JOIN product p ON p.id = soi.product_id
             WHERE soi.sales_order_id = ?
             ORDER BY soi.id'
        );
        $itemStmt->execute([$id]);

        $items = array_map(fn (array $itemRow) => new SalesOrderItem(
            id: (int) $itemRow['id'],
            productId: (int) $itemRow['product_id'],
            productName: $itemRow['product_name'],
            qty: (int) $itemRow['qty'],
            sellPrice: (float) $itemRow['sell_price'],
        ), $itemStmt->fetchAll());

        return $this->toEntity($row, $items);
    }

    public function create(int $customerId, int $warehouseId, string $orderDate, int $createdBy): SalesOrder
    {
        $stmt = Database::connection()->prepare(
            "INSERT INTO sales_order (customer_id, warehouse_id, status, order_date, created_by)
             VALUES (?, ?, 'Draft', ?, ?)"
        );
        $stmt->execute([$customerId, $warehouseId, $orderDate, $createdBy]);
        $id = (int) Database::connection()->lastInsertId();

        return $this->findById($id);
    }

    public function addItem(int $salesOrderId, int $productId, int $qty, float $sellPrice): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO sales_order_item (sales_order_id, product_id, qty, sell_price) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$salesOrderId, $productId, $qty, $sellPrice]);
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = Database::connection()->prepare('UPDATE sales_order SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    public function approve(int $id, int $approvedBy): void
    {
        $stmt = Database::connection()->prepare(
            "UPDATE sales_order SET status = 'Approved', approved_by = ? WHERE id = ?"
        );
        $stmt->execute([$approvedBy, $id]);
    }

    /**
     * @return array{items: SalesOrder[], total: int}
     */
    public function search(?string $q, ?string $status, string $sort, int $page, int $perPage, ?int $createdBy = null): array
    {
        $conditions = [];
        $params = [];

        if ($q !== null && $q !== '') {
            $conditions[] = '(so.id = ? OR c.name LIKE ?)';
            $params[] = ctype_digit($q) ? (int) $q : 0;
            $params[] = '%' . $q . '%';
        }

        if ($status !== null && $status !== '') {
            $conditions[] = 'so.status = ?';
            $params[] = $status;
        }

        if ($createdBy !== null) {
            $conditions[] = 'so.created_by = ?';
            $params[] = $createdBy;
        }

        $where = $conditions === [] ? '' : ('WHERE ' . implode(' AND ', $conditions));
        $direction = $sort === 'date_asc' ? 'ASC' : 'DESC';

        // Heredoc (bukan nowdoc): perlu interpolasi $where, jadi tanpa
        // quote di SQL pembuka.
        $baseFrom = <<<SQL

            FROM sales_order so
            JOIN customer c ON c.id = so.customer_id
            JOIN warehouse w ON w.id = so.warehouse_id
            JOIN user creator ON creator.id = so.created_by
            LEFT JOIN user approver ON approver.id = so.approved_by
            $where
            SQL;

        $countStmt = Database::connection()->prepare("SELECT COUNT(*) $baseFrom");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $listStmt = Database::connection()->prepare(
            "SELECT so.id, so.customer_id, c.name AS customer_name, so.warehouse_id, w.name AS warehouse_name,
                    so.status, so.created_by, creator.name AS created_by_name,
                    so.approved_by, approver.name AS approved_by_name, so.order_date
             $baseFrom
             ORDER BY so.order_date $direction, so.id $direction
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $listStmt->execute($params);

        $items = array_map(fn (array $row) => $this->toEntity($row, []), $listStmt->fetchAll());

        return ['items' => $items, 'total' => $total];
    }

    /** @param SalesOrderItem[] $items */
    private function toEntity(array $row, array $items): SalesOrder
    {
        return new SalesOrder(
            id: (int) $row['id'],
            customerId: (int) $row['customer_id'],
            customerName: $row['customer_name'],
            warehouseId: (int) $row['warehouse_id'],
            warehouseName: $row['warehouse_name'],
            status: $row['status'],
            createdBy: (int) $row['created_by'],
            createdByName: $row['created_by_name'],
            approvedBy: $row['approved_by'] !== null ? (int) $row['approved_by'] : null,
            approvedByName: $row['approved_by_name'],
            orderDate: $row['order_date'],
            items: $items,
        );
    }
}
