<?php

namespace Tests\Unit;

use App\Entity\Customer;
use App\Entity\Product;
use App\Entity\SalesOrder;
use App\Entity\SalesOrderItem;
use App\Entity\Warehouse;
use App\Repository\CustomerRepositoryInterface;
use App\Repository\GoodsIssueRepositoryInterface;
use App\Repository\ProductRepositoryInterface;
use App\Repository\SalesOrderRepositoryInterface;
use App\Repository\WarehouseRepositoryInterface;
use App\Service\SalesOrderService;
use App\Support\InsufficientStockException;
use PHPUnit\Framework\TestCase;

/**
 * TEST-01: aturan alur kerja Sales Order (SO-01) - murni logika Service.
 * Semua repository adalah mock/stub dari interface-nya, tanpa database.
 * Melengkapi SalesOrderServiceTest yang sudah ada (validasi header/item/issue).
 */
final class SalesOrderServiceWorkflowTest extends TestCase
{
    private function makeService(
        ?SalesOrderRepositoryInterface $orders = null,
        ?ProductRepositoryInterface $products = null,
        ?CustomerRepositoryInterface $customers = null,
        ?WarehouseRepositoryInterface $warehouses = null,
        ?GoodsIssueRepositoryInterface $goodsIssue = null,
    ): SalesOrderService {
        return new SalesOrderService(
            $orders ?? $this->createStub(SalesOrderRepositoryInterface::class),
            $products ?? $this->createStub(ProductRepositoryInterface::class),
            $customers ?? $this->createStub(CustomerRepositoryInterface::class),
            $warehouses ?? $this->createStub(WarehouseRepositoryInterface::class),
            $goodsIssue ?? $this->createStub(GoodsIssueRepositoryInterface::class),
        );
    }

    /** @param SalesOrderItem[] $items */
    private function makeOrder(
        string $status = 'Draft',
        int $createdBy = 5,
        array $items = [],
        int $id = 10,
        int $warehouseId = 2,
    ): SalesOrder {
        return new SalesOrder(
            id: $id,
            customerId: 1,
            customerName: 'Klinik Bella',
            warehouseId: $warehouseId,
            warehouseName: 'Surabaya Branch Warehouse',
            status: $status,
            createdBy: $createdBy,
            createdByName: 'Sales Demo',
            approvedBy: null,
            approvedByName: null,
            orderDate: '2026-10-07',
            items: $items,
        );
    }

    private function makeItem(int $id = 1, int $productId = 7, string $name = 'Cleanser', int $qty = 4): SalesOrderItem
    {
        return new SalesOrderItem(
            id: $id,
            productId: $productId,
            productName: $name,
            qty: $qty,
            sellPrice: 1500.0,
        );
    }

    private function makeProduct(): Product
    {
        return new Product(
            id: 7,
            sku: 'SKU-1',
            name: 'Cleanser',
            categoryId: 3,
            unit: 'pcs',
            buyPrice: 1000.0,
            sellPrice: 1500.0,
            reorderPoint: 5,
            imagePath: null,
            isActive: true,
        );
    }

    /** Repository tiruan yang mengembalikan $order untuk id 10. */
    private function ordersReturning(?SalesOrder $order): SalesOrderRepositoryInterface
    {
        $orders = $this->createMock(SalesOrderRepositoryInterface::class);
        $orders->method('findById')->with(10)->willReturn($order);

        return $orders;
    }

    // ---------- baca / cari / export ----------

    public function test_list_all_and_find_by_id_return_what_the_repository_provides(): void
    {
        $order = $this->makeOrder();
        $orders = $this->createStub(SalesOrderRepositoryInterface::class);
        $orders->method('findAll')->willReturn([$order]);
        $orders->method('findById')->willReturn($order);

        $service = $this->makeService($orders);

        $this->assertSame([$order], $service->listAll());
        $this->assertSame($order, $service->findById(10));
    }

    public function test_search_computes_pagination_and_passes_the_owner_filter(): void
    {
        $orders = $this->createMock(SalesOrderRepositoryInterface::class);
        // $createdBy = 5: user Sales hanya boleh melihat order miliknya sendiri.
        $orders->expects($this->once())
            ->method('search')
            ->with('bella', 'Draft', 'date_asc', 2, 10, 5)
            ->willReturn(['items' => [], 'total' => 25]);

        $result = $this->makeService($orders)->searchSalesOrders('bella', 'Draft', 'date_asc', 2, 5);

        $this->assertSame(2, $result['page']);
        $this->assertSame(10, $result['perPage']);
        $this->assertSame(25, $result['total']);
        $this->assertSame(3, $result['totalPages']);
    }

    public function test_search_clamps_the_page_and_never_reports_zero_pages(): void
    {
        $orders = $this->createMock(SalesOrderRepositoryInterface::class);
        $orders->expects($this->once())
            ->method('search')
            ->with(null, null, 'date_desc', 1, 10, null)
            ->willReturn(['items' => [], 'total' => 0]);

        $result = $this->makeService($orders)->searchSalesOrders(null, null, 'date_desc', -3);

        $this->assertSame(1, $result['page']);
        $this->assertSame(1, $result['totalPages']);
    }

    public function test_export_uses_the_same_search_without_the_page_cap_but_keeps_the_owner_filter(): void
    {
        $order = $this->makeOrder();
        $orders = $this->createMock(SalesOrderRepositoryInterface::class);
        $orders->expects($this->once())
            ->method('search')
            ->with('bella', null, 'date_desc', 1, 10000, 5)
            ->willReturn(['items' => [$order], 'total' => 1]);

        $rows = $this->makeService($orders)->exportSalesOrders('bella', null, 'date_desc', 5);

        $this->assertSame([$order], $rows);
    }

    // ---------- validasi header & item ----------

    public function test_header_validation_passes_for_an_existing_customer_warehouse_and_valid_date(): void
    {
        $customers = $this->createStub(CustomerRepositoryInterface::class);
        $customers->method('findById')->willReturn(new Customer(1, 'Klinik Bella', null, null, true));
        $warehouses = $this->createStub(WarehouseRepositoryInterface::class);
        $warehouses->method('findById')->willReturn(new Warehouse(2, 'Surabaya', 'Surabaya', true));

        $errors = $this->makeService(null, null, $customers, $warehouses)->validateHeader(1, 2, '2026-10-07');

        $this->assertSame([], $errors);
    }

    public function test_header_validation_rejects_unknown_customer_and_warehouse(): void
    {
        // Stub default: findById() mengembalikan null -> data tidak ada di database.
        $errors = $this->makeService()->validateHeader(99, 98, '2026-10-07');

        $this->assertContains('Invalid customer.', $errors);
        $this->assertContains('Invalid warehouse.', $errors);
    }

    public function test_header_validation_does_not_query_the_database_for_non_positive_ids(): void
    {
        $customers = $this->createMock(CustomerRepositoryInterface::class);
        $customers->expects($this->never())->method('findById');
        $warehouses = $this->createMock(WarehouseRepositoryInterface::class);
        $warehouses->expects($this->never())->method('findById');

        $errors = $this->makeService(null, null, $customers, $warehouses)->validateHeader(0, -1, '2026-10-07');

        $this->assertContains('Invalid customer.', $errors);
        $this->assertContains('Invalid warehouse.', $errors);
    }

    public function test_item_validation_passes_for_a_draft_order_with_an_existing_product(): void
    {
        $products = $this->createStub(ProductRepositoryInterface::class);
        $products->method('findById')->willReturn($this->makeProduct());

        $errors = $this->makeService(null, $products)->validateItem($this->makeOrder('Draft'), 7, '3');

        $this->assertSame([], $errors);
    }

    public function test_item_validation_rejects_an_unknown_product_and_a_non_numeric_quantity(): void
    {
        $errors = $this->makeService()->validateItem($this->makeOrder('Draft'), 99, 'abc');

        $this->assertContains('Invalid product.', $errors);
        $this->assertContains('Quantity must be a positive whole number.', $errors);
    }

    // ---------- membuat order & menambah item ----------

    public function test_create_sales_order_delegates_to_the_repository(): void
    {
        $order = $this->makeOrder();
        $orders = $this->createMock(SalesOrderRepositoryInterface::class);
        $orders->expects($this->once())->method('create')->with(1, 2, '2026-10-07', 5)->willReturn($order);

        $this->assertSame($order, $this->makeService($orders)->createSalesOrder(1, 2, '2026-10-07', 5));
    }

    public function test_add_item_delegates_to_the_repository(): void
    {
        $orders = $this->createMock(SalesOrderRepositoryInterface::class);
        $orders->expects($this->once())->method('addItem')->with(10, 7, 3, 1500.0);

        $this->makeService($orders)->addItem(10, 7, 3, 1500.0);
    }

    // ---------- submit for approval ----------

    public function test_submit_moves_a_draft_order_with_items_to_pending_approval(): void
    {
        $orders = $this->ordersReturning($this->makeOrder('Draft', 5, [$this->makeItem()]));
        $orders->expects($this->once())->method('updateStatus')->with(10, 'PendingApproval');

        $this->assertTrue($this->makeService($orders)->submitForApproval(10));
    }

    public function test_submit_is_refused_for_an_empty_non_draft_or_missing_order(): void
    {
        $cases = [
            'tanpa item' => $this->makeOrder('Draft', 5, []),
            'bukan Draft' => $this->makeOrder('Approved', 5, [$this->makeItem()]),
            'tidak ditemukan' => null,
        ];

        foreach ($cases as $label => $order) {
            $orders = $this->ordersReturning($order);
            $orders->expects($this->never())->method('updateStatus');

            $this->assertFalse($this->makeService($orders)->submitForApproval(10), "Submit harus ditolak: {$label}");
        }
    }

    // ---------- approve (pemisahan tugas) ----------

    public function test_a_different_user_can_approve_a_pending_order(): void
    {
        // Dibuat oleh user 5, disetujui user 1.
        $orders = $this->ordersReturning($this->makeOrder('PendingApproval', 5));
        $orders->expects($this->once())->method('approve')->with(10, 1);

        $this->assertTrue($this->makeService($orders)->approve(10, 1));
    }

    public function test_the_creator_can_never_approve_their_own_order(): void
    {
        // SO-01: pemisahan tugas ditegakkan di Service, bukan hanya di tampilan.
        $orders = $this->ordersReturning($this->makeOrder('PendingApproval', 5));
        $orders->expects($this->never())->method('approve');

        $this->assertFalse($this->makeService($orders)->approve(10, 5));
    }

    public function test_approve_is_refused_when_the_order_is_not_pending_or_missing(): void
    {
        foreach ([$this->makeOrder('Draft', 5), $this->makeOrder('Approved', 5), null] as $order) {
            $orders = $this->ordersReturning($order);
            $orders->expects($this->never())->method('approve');

            $this->assertFalse($this->makeService($orders)->approve(10, 1));
        }
    }

    // ---------- reject & cancel ----------

    public function test_reject_cancels_a_pending_order_only(): void
    {
        $orders = $this->ordersReturning($this->makeOrder('PendingApproval'));
        $orders->expects($this->once())->method('updateStatus')->with(10, 'Cancelled');
        $this->assertTrue($this->makeService($orders)->reject(10));

        foreach ([$this->makeOrder('Draft'), $this->makeOrder('Approved'), null] as $order) {
            $orders = $this->ordersReturning($order);
            $orders->expects($this->never())->method('updateStatus');
            $this->assertFalse($this->makeService($orders)->reject(10));
        }
    }

    public function test_cancel_is_allowed_until_the_order_is_fulfilled_or_already_cancelled(): void
    {
        foreach (['Draft', 'PendingApproval', 'Approved'] as $status) {
            $orders = $this->ordersReturning($this->makeOrder($status));
            $orders->expects($this->once())->method('updateStatus')->with(10, 'Cancelled');

            $this->assertTrue($this->makeService($orders)->cancel(10), "Cancel harus boleh dari status {$status}");
        }

        foreach ([$this->makeOrder('Fulfilled'), $this->makeOrder('Cancelled'), null] as $order) {
            $orders = $this->ordersReturning($order);
            $orders->expects($this->never())->method('updateStatus');

            $this->assertFalse($this->makeService($orders)->cancel(10));
        }
    }

    // ---------- goods issue ----------

    public function test_process_goods_issue_sends_every_item_to_the_issue_repository(): void
    {
        $order = $this->makeOrder('Approved', 5, [
            $this->makeItem(1, 7, 'Cleanser', 4),
            $this->makeItem(2, 8, 'Toner', 2),
        ], 10, 2);

        $goodsIssue = $this->createMock(GoodsIssueRepositoryInterface::class);
        // Warehouse asal = gudang di SO (2), dan pelaksana = user yang login (3).
        $goodsIssue->expects($this->once())->method('issue')->with(
            10,
            2,
            [
                ['item_id' => 1, 'product_id' => 7, 'product_name' => 'Cleanser', 'qty' => 4],
                ['item_id' => 2, 'product_id' => 8, 'product_name' => 'Toner', 'qty' => 2],
            ],
            3,
        );

        $this->makeService(null, null, null, null, $goodsIssue)->processGoodsIssue($order, 3);
    }

    public function test_process_goods_issue_lets_an_insufficient_stock_error_reach_the_caller(): void
    {
        $goodsIssue = $this->createStub(GoodsIssueRepositoryInterface::class);
        $goodsIssue->method('issue')->willThrowException(new InsufficientStockException('Not enough stock'));

        $this->expectException(InsufficientStockException::class);

        $this->makeService(null, null, null, null, $goodsIssue)
            ->processGoodsIssue($this->makeOrder('Approved', 5, [$this->makeItem()]), 3);
    }
}
