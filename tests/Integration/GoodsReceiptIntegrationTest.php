<?php

namespace Tests\Integration;

use App\Repository\MySqlCategoryRepository;
use App\Repository\MySqlGoodsReceiptRepository;
use App\Repository\MySqlProductRepository;
use App\Repository\MySqlPurchaseOrderRepository;
use App\Repository\MySqlStockRepository;
use App\Repository\MySqlSupplierRepository;
use App\Repository\MySqlWarehouseRepository;
use App\Support\Database;
use PHPUnit\Framework\TestCase;

/**
 * TEST-02: touches the real MySQL database running in Docker.
 * Proves goods receipt genuinely increases product_stock end-to-end,
 * through the exact same transactional path the application uses
 * (MySqlGoodsReceiptRepository::receive).
 */
final class GoodsReceiptIntegrationTest extends TestCase
{
    private int $categoryId;
    private int $productId;
    private int $warehouseId;
    private int $supplierId;
    private int $userId;
    private int $purchaseOrderId;
    private int $itemId;

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

        $supplier = (new MySqlSupplierRepository())->create('Integration Test Supplier', null, null);
        $this->supplierId = $supplier->id;

        $this->userId = (int) $pdo->query('SELECT id FROM user LIMIT 1')->fetchColumn();

        $poRepo = new MySqlPurchaseOrderRepository();
        $po = $poRepo->create($supplier->id, $warehouse->id, '2026-01-01', $this->userId);
        $poRepo->addItem($po->id, $product->id, 10, 1000.0);
        $po = $poRepo->findById($po->id);

        $this->purchaseOrderId = $po->id;
        $this->itemId = $po->items[0]->id;
    }

    protected function tearDown(): void
    {
        $pdo = Database::connection();
        $pdo->prepare('DELETE FROM stock_ledger WHERE product_id = ?')->execute([$this->productId]);
        $pdo->prepare('DELETE FROM purchase_order_item WHERE purchase_order_id = ?')->execute([$this->purchaseOrderId]);
        $pdo->prepare('DELETE FROM purchase_order WHERE id = ?')->execute([$this->purchaseOrderId]);
        $pdo->prepare('DELETE FROM product_stock WHERE product_id = ?')->execute([$this->productId]);
        $pdo->prepare('DELETE FROM product WHERE id = ?')->execute([$this->productId]);
        $pdo->prepare('DELETE FROM supplier WHERE id = ?')->execute([$this->supplierId]);
        $pdo->prepare('DELETE FROM warehouse WHERE id = ?')->execute([$this->warehouseId]);
        $pdo->prepare('DELETE FROM category WHERE id = ?')->execute([$this->categoryId]);
    }

    public function test_goods_receipt_increases_stock_end_to_end(): void
    {
        (new MySqlGoodsReceiptRepository())->receive(
            $this->purchaseOrderId,
            $this->itemId,
            $this->productId,
            $this->warehouseId,
            6,
            $this->userId,
        );

        $total = (new MySqlStockRepository())->getTotalForProduct($this->productId);
        $this->assertSame(6, $total);

        $po = (new MySqlPurchaseOrderRepository())->findById($this->purchaseOrderId);
        $this->assertSame('PartiallyReceived', $po->status);
        $this->assertSame(6, $po->items[0]->qtyReceived);
    }

    public function test_full_receipt_marks_purchase_order_as_received(): void
    {
        (new MySqlGoodsReceiptRepository())->receive(
            $this->purchaseOrderId,
            $this->itemId,
            $this->productId,
            $this->warehouseId,
            10,
            $this->userId,
        );

        $po = (new MySqlPurchaseOrderRepository())->findById($this->purchaseOrderId);
        $this->assertSame('Received', $po->status);

        $total = (new MySqlStockRepository())->getTotalForProduct($this->productId);
        $this->assertSame(10, $total);
    }
}
