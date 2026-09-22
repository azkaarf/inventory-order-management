<?php

namespace Tests\Unit;

use App\Entity\SalesOrder;
use App\Repository\MySqlCustomerRepository;
use App\Repository\MySqlGoodsIssueRepository;
use App\Repository\MySqlProductRepository;
use App\Repository\MySqlSalesOrderRepository;
use App\Repository\MySqlWarehouseRepository;
use App\Service\SalesOrderService;
use PHPUnit\Framework\TestCase;

final class SalesOrderServiceTest extends TestCase
{
    /**
     * Note: same trick as PurchaseOrderServiceTest — passing customerId/
     * warehouseId/productId = 0 short-circuits before any repository method
     * is called, so it's safe to use real MySql* repositories here without
     * a DB connection.
     */
    private function makeService(): SalesOrderService
    {
        return new SalesOrderService(
            new MySqlSalesOrderRepository(),
            new MySqlProductRepository(),
            new MySqlCustomerRepository(),
            new MySqlWarehouseRepository(),
            new MySqlGoodsIssueRepository(),
        );
    }

    public function test_header_validation_fails_for_invalid_date_format(): void
    {
        $errors = $this->makeService()->validateHeader(0, 0, 'not-a-date');

        $this->assertContains('Order date is invalid.', $errors);
    }

    public function test_item_validation_fails_when_so_is_not_draft(): void
    {
        $so = new SalesOrder(1, 1, 'Customer', 1, 'Warehouse', 'Approved', 1, 'Creator', null, null, '2026-01-01', []);

        $errors = $this->makeService()->validateItem($so, 0, '5');

        $this->assertContains('Items can only be added while the sales order is still a Draft.', $errors);
    }

    public function test_item_validation_fails_for_non_positive_quantity(): void
    {
        $so = new SalesOrder(1, 1, 'Customer', 1, 'Warehouse', 'Draft', 1, 'Creator', null, null, '2026-01-01', []);

        $errors = $this->makeService()->validateItem($so, 0, '0');

        $this->assertContains('Quantity must be a positive whole number.', $errors);
    }

    public function test_goods_issue_validation_fails_when_not_approved(): void
    {
        $so = new SalesOrder(1, 1, 'Customer', 1, 'Warehouse', 'PendingApproval', 1, 'Creator', null, null, '2026-01-01', []);

        $errors = $this->makeService()->validateGoodsIssue($so);

        $this->assertContains('Goods issue can only be processed for an Approved sales order.', $errors);
    }

    public function test_goods_issue_validation_passes_when_approved(): void
    {
        $so = new SalesOrder(1, 1, 'Customer', 1, 'Warehouse', 'Approved', 1, 'Creator', null, null, '2026-01-01', []);

        $errors = $this->makeService()->validateGoodsIssue($so);

        $this->assertEmpty($errors);
    }
}
