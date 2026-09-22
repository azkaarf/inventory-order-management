<?php

namespace App\Repository;

use App\Support\Database;
use Throwable;

final class MySqlGoodsReceiptRepository implements GoodsReceiptRepositoryInterface
{
    public function receive(
        int $purchaseOrderId,
        int $itemId,
        int $productId,
        int $warehouseId,
        int $qtyReceived,
        int $performedBy,
    ): void {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $pdo->prepare(
                "INSERT INTO stock_ledger (product_id, warehouse_id, movement_type, quantity, reference_type, reference_id, performed_by)
                 VALUES (?, ?, 'Receipt', ?, 'PurchaseOrder', ?, ?)"
            )->execute([$productId, $warehouseId, $qtyReceived, $purchaseOrderId, $performedBy]);

            $pdo->prepare(
                'UPDATE product_stock SET quantity = quantity + ? WHERE product_id = ? AND warehouse_id = ?'
            )->execute([$qtyReceived, $productId, $warehouseId]);

            $pdo->prepare(
                'UPDATE purchase_order_item SET qty_received = qty_received + ? WHERE id = ?'
            )->execute([$qtyReceived, $itemId]);

            $totalsStmt = $pdo->prepare(
                'SELECT SUM(qty_ordered) AS total_ordered, SUM(qty_received) AS total_received
                 FROM purchase_order_item WHERE purchase_order_id = ?'
            );
            $totalsStmt->execute([$purchaseOrderId]);
            $totals = $totalsStmt->fetch();

            $newStatus = ((int) $totals['total_received'] >= (int) $totals['total_ordered'])
                ? 'Received'
                : 'PartiallyReceived';

            $pdo->prepare('UPDATE purchase_order SET status = ? WHERE id = ?')
                ->execute([$newStatus, $purchaseOrderId]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
