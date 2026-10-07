<?php

namespace Tests\Unit;

use App\Entity\Product;
use App\Entity\PurchaseOrder;
use App\Entity\PurchaseOrderItem;
use App\Entity\Supplier;
use App\Entity\Warehouse;
use App\Repository\GoodsReceiptRepositoryInterface;
use App\Repository\ProductRepositoryInterface;
use App\Repository\PurchaseOrderRepositoryInterface;
use App\Repository\SupplierRepositoryInterface;
use App\Repository\WarehouseRepositoryInterface;
use App\Service\PurchaseOrderService;
use PHPUnit\Framework\TestCase;

/**
 * TEST-01: aturan alur kerja Purchase Order (PO-01) - murni logika Service.
 * Semua repository adalah mock/stub dari interface-nya, tanpa database.
 * Melengkapi PurchaseOrderServiceTest yang sudah ada.
 */
final class PurchaseOrderServiceWorkflowTest extends TestCase
{
    private function makeService(
        ?PurchaseOrderRepositoryInterface $orders = null,
        ?ProductRepositoryInterface $products = null,
        ?SupplierRepositoryInterface $suppliers = null,
        ?WarehouseRepositoryInterface $warehouses = null,
        ?GoodsReceiptRepositoryInterface $goodsReceipt = null,
    ): PurchaseOrderService {
        return new PurchaseOrderService(
            $orders ?? $this->createStub(PurchaseOrderRepositoryInterface::class),
            $products ?? $this->createStub(ProductRepositoryInterface::class),
            $suppliers ?? $this->createStub(SupplierRepositoryInterface::class),
            $warehouses ?? $this->createStub(WarehouseRepositoryInterface::class),
            $goodsReceipt ?? $this->createStub(GoodsReceiptRepositoryInterface::class),
        );
    }

    /** @param PurchaseOrderItem[] $items */
    private function makePo(string $status = 'Draft', array $items = [], int $id = 12, int $warehouseId = 2): PurchaseOrder
    {
        return new PurchaseOrder(
            id: $id,
            supplierId: 1,
            supplierName: 'PT Kosmetika Cantik Abadi',
            warehouseId: $warehouseId,
            warehouseName: 'Surabaya Branch Warehouse',
            status: $status,
            orderDate: '2026-10-07',
            createdBy: 3,
            items: $items,
        );
    }

    private function makeItem(int $id = 3, int $productId = 7, int $ordered = 10, int $received = 0): PurchaseOrderItem
    {
        return new PurchaseOrderItem(
            id: $id,
            productId: $productId,
            productName: 'Cleanser',
            qtyOrdered: $ordered,
            qtyReceived: $received,
            buyPrice: 1000.0,
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

    /** Repository tiruan yang mengembalikan $po untuk id 12. */
    private function ordersReturning(?PurchaseOrder $po): PurchaseOrderRepositoryInterface
    {
        $orders = $this->createMock(PurchaseOrderRepositoryInterface::class);
        $orders->method('findById')->with(12)->willReturn($po);

        return $orders;
    }

    // ---------- baca / cari / export ----------

    public function test_list_and_find_by_id_return_what_the_repository_provides(): void
    {
        $po = $this->makePo();
        $orders = $this->createStub(PurchaseOrderRepositoryInterface::class);
        $orders->method('findAll')->willReturn([$po]);
        $orders->method('findById')->willReturn($po);

        $service = $this->makeService($orders);

        $this->assertSame([$po], $service->listPurchaseOrders());
        $this->assertSame($po, $service->findById(12));
    }

    public function test_search_computes_pagination_from_the_repository_total(): void
    {
        $orders = $this->createMock(PurchaseOrderRepositoryInterface::class);
        $orders->expects($this->once())
            ->method('search')
            ->with('kosmetika', 'Ordered', 'date_desc', 3, 10)
            ->willReturn(['items' => [], 'total' => 25]);

        $result = $this->makeService($orders)->searchPurchaseOrders('kosmetika', 'Ordered', 'date_desc', 3);

        $this->assertSame(3, $result['page']);
        $this->assertSame(10, $result['perPage']);
        $this->assertSame(25, $result['total']);
        $this->assertSame(3, $result['totalPages']);
    }

    public function test_search_clamps_the_page_and_never_reports_zero_pages(): void
    {
        $orders = $this->createMock(PurchaseOrderRepositoryInterface::class);
        $orders->expects($this->once())
            ->method('search')
            ->with(null, null, 'date_desc', 1, 10)
            ->willReturn(['items' => [], 'total' => 0]);

        $result = $this->makeService($orders)->searchPurchaseOrders(null, null, 'date_desc', 0);

        $this->assertSame(1, $result['page']);
        $this->assertSame(1, $result['totalPages']);
    }

    public function test_export_uses_the_same_search_without_the_page_cap(): void
    {
        $po = $this->makePo();
        $orders = $this->createMock(PurchaseOrderRepositoryInterface::class);
        $orders->expects($this->once())
            ->method('search')
            ->with('kosmetika', null, 'date_asc', 1, 10000)
            ->willReturn(['items' => [$po], 'total' => 1]);

        $this->assertSame([$po], $this->makeService($orders)->exportPurchaseOrders('kosmetika', null, 'date_asc'));
    }

    // ---------- validasi header & item ----------

    public function test_header_validation_passes_for_an_existing_supplier_warehouse_and_valid_date(): void
    {
        $suppliers = $this->createStub(SupplierRepositoryInterface::class);
        $suppliers->method('findById')->willReturn(new Supplier(1, 'PT Kosmetika', null, null, true));
        $warehouses = $this->createStub(WarehouseRepositoryInterface::class);
        $warehouses->method('findById')->willReturn(new Warehouse(2, 'Surabaya', 'Surabaya', true));

        $errors = $this->makeService(null, null, $suppliers, $warehouses)->validateHeader(1, 2, '2026-10-07');

        $this->assertSame([], $errors);
    }

    public function test_header_validation_rejects_unknown_supplier_and_warehouse(): void
    {
        $errors = $this->makeService()->validateHeader(99, 98, '2026-10-07');

        $this->assertContains('Invalid supplier.', $errors);
        $this->assertContains('Invalid warehouse.', $errors);
    }

    public function test_header_validation_does_not_query_the_database_for_non_positive_ids(): void
    {
        $suppliers = $this->createMock(SupplierRepositoryInterface::class);
        $suppliers->expects($this->never())->method('findById');
        $warehouses = $this->createMock(WarehouseRepositoryInterface::class);
        $warehouses->expects($this->never())->method('findById');

        $errors = $this->makeService(null, null, $suppliers, $warehouses)->validateHeader(0, -1, '2026-10-07');

        $this->assertContains('Invalid supplier.', $errors);
        $this->assertContains('Invalid warehouse.', $errors);
    }

    public function test_item_validation_passes_for_a_draft_order_with_valid_input(): void
    {
        $products = $this->createStub(ProductRepositoryInterface::class);
        $products->method('findById')->willReturn($this->makeProduct());

        $errors = $this->makeService(null, $products)->validateItem($this->makePo('Draft'), 7, '5', '1000.50');

        $this->assertSame([], $errors);
    }

    public function test_item_validation_rejects_unknown_product_and_bad_prices(): void
    {
        $service = $this->makeService();

        $unknownProduct = $service->validateItem($this->makePo('Draft'), 99, '5', '1000');
        $this->assertContains('Invalid product.', $unknownProduct);

        foreach (['-1', 'abc', ''] as $badPrice) {
            $errors = $service->validateItem($this->makePo('Draft'), 99, '5', $badPrice);

            $this->assertContains(
                'Buy price must be a number and cannot be negative.',
                $errors,
                "Harga '{$badPrice}' seharusnya ditolak",
            );
        }
    }

    // ---------- membuat PO & menambah item ----------

    public function test_create_purchase_order_delegates_to_the_repository(): void
    {
        $po = $this->makePo();
        $orders = $this->createMock(PurchaseOrderRepositoryInterface::class);
        $orders->expects($this->once())->method('create')->with(1, 2, '2026-10-07', 3)->willReturn($po);

        $this->assertSame($po, $this->makeService($orders)->createPurchaseOrder(1, 2, '2026-10-07', 3));
    }

    public function test_add_item_delegates_to_the_repository(): void
    {
        $orders = $this->createMock(PurchaseOrderRepositoryInterface::class);
        $orders->expects($this->once())->method('addItem')->with(12, 7, 10, 1000.0);

        $this->makeService($orders)->addItem(12, 7, 10, 1000.0);
    }

    // ---------- mark as ordered ----------

    public function test_mark_as_ordered_moves_a_draft_order_with_items_to_ordered(): void
    {
        $orders = $this->ordersReturning($this->makePo('Draft', [$this->makeItem()]));
        $orders->expects($this->once())->method('updateStatus')->with(12, 'Ordered');

        $this->assertTrue($this->makeService($orders)->markAsOrdered(12));
    }

    public function test_mark_as_ordered_is_refused_for_an_empty_non_draft_or_missing_order(): void
    {
        $cases = [
            'tanpa item' => $this->makePo('Draft', []),
            'bukan Draft' => $this->makePo('Ordered', [$this->makeItem()]),
            'tidak ditemukan' => null,
        ];

        foreach ($cases as $label => $po) {
            $orders = $this->ordersReturning($po);
            $orders->expects($this->never())->method('updateStatus');

            $this->assertFalse($this->makeService($orders)->markAsOrdered(12), "Ditolak: {$label}");
        }
    }

    // ---------- cancel ----------

    public function test_cancel_is_allowed_until_the_order_is_received_or_already_cancelled(): void
    {
        foreach (['Draft', 'Ordered', 'PartiallyReceived'] as $status) {
            $orders = $this->ordersReturning($this->makePo($status));
            $orders->expects($this->once())->method('updateStatus')->with(12, 'Cancelled');

            $this->assertTrue($this->makeService($orders)->cancel(12), "Cancel harus boleh dari status {$status}");
        }

        foreach ([$this->makePo('Received'), $this->makePo('Cancelled'), null] as $po) {
            $orders = $this->ordersReturning($po);
            $orders->expects($this->never())->method('updateStatus');

            $this->assertFalse($this->makeService($orders)->cancel(12));
        }
    }

    // ---------- goods receipt ----------

    public function test_receipt_validation_rejects_zero_and_negative_quantities(): void
    {
        $po = $this->makePo('Ordered');
        $item = $this->makeItem(3, 7, 10, 0);
        $service = $this->makeService();

        foreach ([0, -5] as $qty) {
            $this->assertContains(
                'Quantity received must be greater than zero.',
                $service->validateReceipt($po, $item, $qty),
                "Qty {$qty} seharusnya ditolak",
            );
        }
    }

    public function test_receipt_validation_accepts_exactly_the_remaining_quantity(): void
    {
        // Dipesan 10, sudah diterima 6 -> sisa 4. Menerima persis 4 harus lolos.
        $item = $this->makeItem(3, 7, 10, 6);

        $errors = $this->makeService()->validateReceipt($this->makePo('PartiallyReceived'), $item, 4);

        $this->assertSame([], $errors);
    }

    public function test_receive_item_sends_the_po_item_product_and_warehouse_to_the_receipt_repository(): void
    {
        $po = $this->makePo('Ordered', [$this->makeItem()], 12, 2);
        $item = $this->makeItem(3, 7, 10, 0);

        $goodsReceipt = $this->createMock(GoodsReceiptRepositoryInterface::class);
        // (id PO, id item, id produk, gudang tujuan PO, qty diterima, user pelaksana)
        $goodsReceipt->expects($this->once())->method('receive')->with(12, 3, 7, 2, 5, 9);

        $this->makeService(null, null, null, null, $goodsReceipt)->receiveItem($po, $item, 5, 9);
    }
}
