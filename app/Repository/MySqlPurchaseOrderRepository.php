<?php

namespace App\Repository;

use App\Entity\PurchaseOrder;
use App\Entity\PurchaseOrderItem;
use App\Support\Database;

final class MySqlPurchaseOrderRepository implements PurchaseOrderRepositoryInterface
{
    public function findAll(): array
    {
        $stmt = Database::connection()->query(
            'SELECT po.id, po.supplier_id, s.name AS supplier_name, po.warehouse_id, w.name AS warehouse_name,
                    po.status, po.order_date, po.created_by
             FROM purchase_order po
             JOIN supplier s ON s.id = po.supplier_id
             JOIN warehouse w ON w.id = po.warehouse_id
             ORDER BY po.order_date DESC, po.id DESC'
        );

        return array_map(fn (array $row) => $this->toEntity($row, []), $stmt->fetchAll());
    }

    public function findById(int $id): ?PurchaseOrder
    {
        $stmt = Database::connection()->prepare(
            'SELECT po.id, po.supplier_id, s.name AS supplier_name, po.warehouse_id, w.name AS warehouse_name,
                    po.status, po.order_date, po.created_by
             FROM purchase_order po
             JOIN supplier s ON s.id = po.supplier_id
             JOIN warehouse w ON w.id = po.warehouse_id
             WHERE po.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        $itemStmt = Database::connection()->prepare(
            'SELECT poi.id, poi.product_id, p.name AS product_name, poi.qty_ordered, poi.qty_received, poi.buy_price
             FROM purchase_order_item poi
             JOIN product p ON p.id = poi.product_id
             WHERE poi.purchase_order_id = ?
             ORDER BY poi.id'
        );
        $itemStmt->execute([$id]);

        $items = array_map(fn (array $itemRow) => new PurchaseOrderItem(
            id: (int) $itemRow['id'],
            productId: (int) $itemRow['product_id'],
            productName: $itemRow['product_name'],
            qtyOrdered: (int) $itemRow['qty_ordered'],
            qtyReceived: (int) $itemRow['qty_received'],
            buyPrice: (float) $itemRow['buy_price'],
        ), $itemStmt->fetchAll());

        return $this->toEntity($row, $items);
    }

    public function create(int $supplierId, int $warehouseId, string $orderDate, int $createdBy): PurchaseOrder
    {
        $stmt = Database::connection()->prepare(
            "INSERT INTO purchase_order (supplier_id, warehouse_id, status, order_date, created_by)
             VALUES (?, ?, 'Draft', ?, ?)"
        );
        $stmt->execute([$supplierId, $warehouseId, $orderDate, $createdBy]);
        $id = (int) Database::connection()->lastInsertId();

        return $this->findById($id);
    }

    public function addItem(int $purchaseOrderId, int $productId, int $qtyOrdered, float $buyPrice): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO purchase_order_item (purchase_order_id, product_id, qty_ordered, buy_price) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$purchaseOrderId, $productId, $qtyOrdered, $buyPrice]);
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = Database::connection()->prepare('UPDATE purchase_order SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    /**
     * @return array{items: PurchaseOrder[], total: int}
     */
    public function search(?string $q, ?string $status, string $sort, int $page, int $perPage): array
    {
        $conditions = [];
        $params = [];

        if ($q !== null && $q !== '') {
            $conditions[] = '(po.id = ? OR s.name LIKE ?)';
            $params[] = ctype_digit($q) ? (int) $q : 0;
            $params[] = '%' . $q . '%';
        }

        if ($status !== null && $status !== '') {
            $conditions[] = 'po.status = ?';
            $params[] = $status;
        }

        $where = $conditions === [] ? '' : ('WHERE ' . implode(' AND ', $conditions));
        $direction = $sort === 'date_asc' ? 'ASC' : 'DESC';

        // Heredoc: perlu interpolasi $where.
        $baseFrom = <<<SQL

            FROM purchase_order po
            JOIN supplier s ON s.id = po.supplier_id
            JOIN warehouse w ON w.id = po.warehouse_id
            $where
            SQL;

        $countStmt = Database::connection()->prepare("SELECT COUNT(*) $baseFrom");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $listStmt = Database::connection()->prepare(
            "SELECT po.id, po.supplier_id, s.name AS supplier_name, po.warehouse_id, w.name AS warehouse_name,
                    po.status, po.order_date, po.created_by
             $baseFrom
             ORDER BY po.order_date $direction, po.id $direction
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $listStmt->execute($params);

        $items = array_map(fn (array $row) => $this->toEntity($row, []), $listStmt->fetchAll());

        return ['items' => $items, 'total' => $total];
    }

    /** @param PurchaseOrderItem[] $items */
    private function toEntity(array $row, array $items): PurchaseOrder
    {
        return new PurchaseOrder(
            id: (int) $row['id'],
            supplierId: (int) $row['supplier_id'],
            supplierName: $row['supplier_name'],
            warehouseId: (int) $row['warehouse_id'],
            warehouseName: $row['warehouse_name'],
            status: $row['status'],
            orderDate: $row['order_date'],
            createdBy: (int) $row['created_by'],
            items: $items,
        );
    }
}
