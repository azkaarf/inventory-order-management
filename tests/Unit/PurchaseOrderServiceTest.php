<?php

namespace Tests\Unit;

use App\Entity\PurchaseOrder;
use App\Entity\PurchaseOrderItem;
use App\Repository\MySqlGoodsReceiptRepository;
use App\Repository\MySqlProductRepository;
use App\Repository\MySqlPurchaseOrderRepository;
use App\Repository\MySqlSupplierRepository;
use App\Repository\MySqlWarehouseRepository;
use App\Service\PurchaseOrderService;
use PHPUnit\Framework\TestCase;

final class PurchaseOrderServiceTest extends TestCase
{
    /**
     * Note: with supplierId/warehouseId/productId = 0, the "<= 0" check in the
     * Service short-circuits before any repository method is called, so it's
     * safe to construct real MySql* repositories here without a DB connection.
     */
    private function makeService(): PurchaseOrderService
    {
        return new PurchaseOrderService(
            new MySqlPurchaseOrderRepository(),
            new MySqlProductRepository(),
            new MySqlSupplierRepository(),
            new MySqlWarehouseRepository(),
            new MySqlGoodsReceiptRepository(),
        );
    }

    public function test_header_validation_fails_for_invalid_date_format(): void
    {
        $errors = $this->makeService()->validateHeader(0, 0, 'not-a-date');

        $this->assertContains('Order date is invalid.', $errors);
    }

    public function test_header_validation_does_not_flag_a_correctly_formatted_date(): void
    {
        $errors = $this->makeService()->validateHeader(0, 0, '2026-01-15');

        $this->assertNotContains('Order date is invalid.', $errors);
    }

    public function test_item_validation_fails_when_po_is_not_draft(): void
    {
        $po = new PurchaseOrder(1, 1, 'Supplier', 1, 'Warehouse', 'Ordered', '2026-01-01', 1, []);

        $errors = $this->makeService()->validateItem($po, 0, '5', '1000');

        $this->assertContains('Items can only be added while the purchase order is still a Draft.', $errors);
    }

    public function test_item_validation_fails_for_non_positive_quantity(): void
    {
        $po = new PurchaseOrder(1, 1, 'Supplier', 1, 'Warehouse', 'Draft', '2026-01-01', 1, []);

        $errors = $this->makeService()->validateItem($po, 0, '0', '1000');

        $this->assertContains('Quantity must be a positive whole number.', $errors);
    }

    public function test_receipt_validation_fails_when_qty_exceeds_remaining(): void
    {
        $po = new PurchaseOrder(1, 1, 'Supplier', 1, 'Warehouse', 'Ordered', '2026-01-01', 1, []);
        $item = new PurchaseOrderItem(1, 1, 'Product', 10, 4, 1000.0); // remaining = 6

        $errors = $this->makeService()->validateReceipt($po, $item, 7);

        $this->assertContains('Quantity received cannot exceed the remaining ordered quantity.', $errors);
    }

    public function test_receipt_validation_fails_when_po_status_not_receivable(): void
    {
        $po = new PurchaseOrder(1, 1, 'Supplier', 1, 'Warehouse', 'Draft', '2026-01-01', 1, []);
        $item = new PurchaseOrderItem(1, 1, 'Product', 10, 0, 1000.0);

        $errors = $this->makeService()->validateReceipt($po, $item, 5);

        $this->assertContains('This purchase order is not in a receivable status.', $errors);
    }

    public function test_receipt_validation_passes_for_a_valid_partial_receipt(): void
    {
        $po = new PurchaseOrder(1, 1, 'Supplier', 1, 'Warehouse', 'PartiallyReceived', '2026-01-01', 1, []);
        $item = new PurchaseOrderItem(1, 1, 'Product', 10, 4, 1000.0); // remaining = 6

        $errors = $this->makeService()->validateReceipt($po, $item, 6);

        $this->assertEmpty($errors);
    }
}
