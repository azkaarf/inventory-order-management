<?php

namespace App\Repository;

use App\Support\Database;
use App\Support\InsufficientStockException;
use Throwable;

final class MySqlGoodsIssueRepository implements GoodsIssueRepositoryInterface
{
    public function issue(int $salesOrderId, int $warehouseId, array $items, int $performedBy): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            // 1. Lock every affected row first (SELECT ... FOR UPDATE) and check
            //    availability. A second, concurrent goods-issue attempt against
            //    the same product+warehouse will block here until this
            //    transaction commits or rolls back — that's what makes this
            //    concurrency-safe rather than a plain read-then-write race.
            foreach ($items as $item) {
                $stmt = $pdo->prepare(
                    'SELECT quantity FROM product_stock WHERE product_id = ? AND warehouse_id = ? FOR UPDATE'
                );
                $stmt->execute([$item['product_id'], $warehouseId]);
                $available = $stmt->fetchColumn();

                if ($available === false || (int) $available < $item['qty']) {
                    throw new InsufficientStockException(sprintf(
                        'Not enough stock for "%s" (available: %d, requested: %d).',
                        $item['product_name'],
                        $available === false ? 0 : (int) $available,
                        $item['qty'],
                    ));
                }
            }

            // 2. Every item checked out — apply all the writes.
            foreach ($items as $item) {
                $pdo->prepare(
                    'UPDATE product_stock SET quantity = quantity - ? WHERE product_id = ? AND warehouse_id = ?'
                )->execute([$item['qty'], $item['product_id'], $warehouseId]);

                $pdo->prepare(
                    "INSERT INTO stock_ledger (product_id, warehouse_id, movement_type, quantity, reference_type, reference_id, performed_by)
                     VALUES (?, ?, 'Issue', ?, 'SalesOrder', ?, ?)"
                )->execute([$item['product_id'], $warehouseId, $item['qty'], $salesOrderId, $performedBy]);
            }

            $pdo->prepare("UPDATE sales_order SET status = 'Fulfilled' WHERE id = ?")->execute([$salesOrderId]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
