<?php

namespace Tests\Integration;

use App\Repository\MySqlCategoryRepository;
use App\Repository\MySqlCustomerRepository;
use App\Repository\MySqlGoodsIssueRepository;
use App\Repository\MySqlProductRepository;
use App\Repository\MySqlSalesOrderRepository;
use App\Repository\MySqlStockRepository;
use App\Repository\MySqlWarehouseRepository;
use App\Support\Database;
use App\Support\InsufficientStockException;
use PHPUnit\Framework\TestCase;

/**
 * TEST-02 / ARCH-02: proves that once stock is exhausted by one goods issue,
 * a second attempt against the same product+warehouse is rejected rather
 * than allowed to oversell — the exact scenario the brief describes as
 * sufficient proof (a controlled sequential scenario, not a real
 * parallel/thread simulation, which the brief explicitly says isn't required).
 */
final class GoodsIssueIntegrationTest extends TestCase
{
    private int $categoryId;
    private int $productId;
    private int $warehouseId;
    private int $customerId;
    private int $userId;
    private int $salesOrderOneId;
    private int $salesOrderTwoId;

    protected function setUp(): void
    {
        $pdo = Database::connection();

        $category = (new MySqlCategoryRepository())->create('Integration Test Category', null);
        $this->categoryId = $category->id;

        $product = (new MySqlProductRepository())->create(
            'TEST-SKU-' . bin2hex(random_bytes(4)),
            'Integration Test Product',
            $category->id,
            'pcs',
            1000.0,
            1500.0,
            5,
            null,
        );
        $this->productId = $product->id;

        $warehouse = (new MySqlWarehouseRepository())->create('Integration Test Warehouse', 'Test City');
        $this->warehouseId = $warehouse->id;

        (new MySqlStockRepository())->initializeForProduct($product->id);
        // Seed exactly 10 units so the second issue attempt provably fails.
        $pdo->prepare('UPDATE product_stock SET quantity = 10 WHERE product_id = ? AND warehouse_id = ?')
            ->execute([$product->id, $warehouse->id]);

        $customer = (new MySqlCustomerRepository())->create('Integration Test Customer', null, null);
        $this->customerId = $customer->id;

        $this->userId = (int) $pdo->query('SELECT id FROM user LIMIT 1')->fetchColumn();

        $soRepo = new MySqlSalesOrderRepository();

        $soOne = $soRepo->create($customer->id, $warehouse->id, '2026-01-01', $this->userId);
        $soRepo->addItem($soOne->id, $product->id, 7, 1500.0);
        $this->salesOrderOneId = $soOne->id;

        $soTwo = $soRepo->create($customer->id, $warehouse->id, '2026-01-01', $this->userId);
        $soRepo->addItem($soTwo->id, $product->id, 7, 1500.0);
        $this->salesOrderTwoId = $soTwo->id;
    }

    protected function tearDown(): void
    {
        $pdo = Database::connection();
        $pdo->prepare('DELETE FROM stock_ledger WHERE product_id = ?')->execute([$this->productId]);
        $pdo->prepare('DELETE FROM sales_order_item WHERE sales_order_id IN (?, ?)')
            ->execute([$this->salesOrderOneId, $this->salesOrderTwoId]);
        $pdo->prepare('DELETE FROM sales_order WHERE id IN (?, ?)')
            ->execute([$this->salesOrderOneId, $this->salesOrderTwoId]);
        $pdo->prepare('DELETE FROM product_stock WHERE product_id = ?')->execute([$this->productId]);
        $pdo->prepare('DELETE FROM product WHERE id = ?')->execute([$this->productId]);
        $pdo->prepare('DELETE FROM customer WHERE id = ?')->execute([$this->customerId]);
        $pdo->prepare('DELETE FROM warehouse WHERE id = ?')->execute([$this->warehouseId]);
        $pdo->prepare('DELETE FROM category WHERE id = ?')->execute([$this->categoryId]);
    }

    public function test_second_goods_issue_is_rejected_once_stock_is_exhausted(): void
    {
        $repo = new MySqlGoodsIssueRepository();

        // First request: 7 out of 10 — succeeds.
        $repo->issue($this->salesOrderOneId, $this->warehouseId, [
            ['item_id' => 0, 'product_id' => $this->productId, 'product_name' => 'Integration Test Product', 'qty' => 7],
        ], $this->userId);

        $remaining = (new MySqlStockRepository())->getTotalForProduct($this->productId);
        $this->assertSame(3, $remaining);

        // Second request: also wants 7, but only 3 remain — must be rejected, not oversold.
        $this->expectException(InsufficientStockException::class);

        $repo->issue($this->salesOrderTwoId, $this->warehouseId, [
            ['item_id' => 0, 'product_id' => $this->productId, 'product_name' => 'Integration Test Product', 'qty' => 7],
        ], $this->userId);
    }

    public function test_stock_is_unchanged_after_a_rejected_issue_attempt(): void
    {
        $repo = new MySqlGoodsIssueRepository();

        $repo->issue($this->salesOrderOneId, $this->warehouseId, [
            ['item_id' => 0, 'product_id' => $this->productId, 'product_name' => 'Integration Test Product', 'qty' => 7],
        ], $this->userId);

        try {
            $repo->issue($this->salesOrderTwoId, $this->warehouseId, [
                ['item_id' => 0, 'product_id' => $this->productId, 'product_name' => 'Integration Test Product', 'qty' => 7],
            ], $this->userId);
        } catch (InsufficientStockException $e) {
            // expected — the point of this test is what happens to stock afterwards
        }

        $remaining = (new MySqlStockRepository())->getTotalForProduct($this->productId);
        $this->assertSame(3, $remaining); // unchanged by the rejected attempt — rollback worked
    }
}
