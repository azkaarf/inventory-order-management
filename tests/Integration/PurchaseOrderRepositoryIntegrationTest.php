<?php

namespace Tests\Integration;

use App\Dto\ProductData;
use App\Entity\PurchaseOrder;
use App\Repository\MySqlCategoryRepository;
use App\Repository\MySqlProductRepository;
use App\Repository\MySqlPurchaseOrderRepository;
use App\Repository\MySqlSupplierRepository;
use App\Repository\MySqlWarehouseRepository;
use App\Support\Database;
use PHPUnit\Framework\TestCase;

/**
 * TEST-02 (PO-01, FIND-01): query Purchase Order terhadap MySQL nyata di
 * database inventory_test. Selain jalur goods receipt yang sudah diuji di
 * GoodsReceiptIntegrationTest, di sini diuji pembuatan PO, item, perubahan
 * status, daftar, dan semua cabang pencarian/filter/sort/pagination.
 *
 * Semua data memakai supplier dengan nama unik, jadi hasil pencarian tidak
 * bercampur dengan seed data, dan semuanya dibersihkan lagi di tearDown.
 */
final class PurchaseOrderRepositoryIntegrationTest extends TestCase
{
    private const SUPPLIER_NAME = 'PORepoTest Alpha Supplier';

    private MySqlPurchaseOrderRepository $repository;
    private int $categoryId;
    private int $productId;
    private int $supplierId;
    private int $otherSupplierId;
    private int $warehouseId;
    private int $userId;

    /** @var int[] */
    private array $poIds = [];

    protected function setUp(): void
    {
        $this->repository = new MySqlPurchaseOrderRepository();

        $this->categoryId = (new MySqlCategoryRepository())->create('PORepoTest Category', null)->id;
        $this->productId = (new MySqlProductRepository())->create(
            'PORT-' . bin2hex(random_bytes(4)),
            new ProductData(
                name: 'PORepoTest Product',
                categoryId: $this->categoryId,
                unit: 'pcs',
                buyPrice: 1000.0,
                sellPrice: 1500.0,
                reorderPoint: 5,
                imagePath: null,
            ),
        )->id;

        $this->supplierId = (new MySqlSupplierRepository())->create(self::SUPPLIER_NAME, null, null)->id;
        $this->otherSupplierId = (new MySqlSupplierRepository())->create('PORepoTest Beta Supplier', null, null)->id;
        $this->warehouseId = (new MySqlWarehouseRepository())->create('PORepoTest Warehouse', 'Test City')->id;
        $this->userId = (int) Database::connection()->query('SELECT id FROM user ORDER BY id LIMIT 1')->fetchColumn();
    }

    protected function tearDown(): void
    {
        $pdo = Database::connection();

        // PO dulu (item ikut terhapus lewat ON DELETE CASCADE), baru produk
        // dan master data - produk dilindungi RESTRICT selama masih ada item.
        foreach ($this->poIds as $id) {
            $pdo->prepare('DELETE FROM purchase_order WHERE id = ?')->execute([$id]);
        }
        $pdo->prepare('DELETE FROM product WHERE id = ?')->execute([$this->productId]);
        $pdo->prepare('DELETE FROM category WHERE id = ?')->execute([$this->categoryId]);
        $pdo->prepare('DELETE FROM supplier WHERE id IN (?, ?)')->execute([$this->supplierId, $this->otherSupplierId]);
        $pdo->prepare('DELETE FROM warehouse WHERE id = ?')->execute([$this->warehouseId]);
    }

    private function makePo(string $orderDate = '2031-01-05', ?int $supplierId = null): PurchaseOrder
    {
        $po = $this->repository->create($supplierId ?? $this->supplierId, $this->warehouseId, $orderDate, $this->userId);
        $this->poIds[] = $po->id;

        return $po;
    }

    /**
     * @param array{items: PurchaseOrder[], total: int} $result
     * @return int[]
     */
    private function idsOf(array $result): array
    {
        return array_map(static fn (PurchaseOrder $po): int => $po->id, $result['items']);
    }

    // ---------- create / findById ----------

    public function test_create_returns_a_draft_order_with_the_joined_names(): void
    {
        $po = $this->makePo('2031-01-05');

        $this->assertGreaterThan(0, $po->id);
        $this->assertSame('Draft', $po->status);
        $this->assertSame(self::SUPPLIER_NAME, $po->supplierName);
        $this->assertSame('PORepoTest Warehouse', $po->warehouseName);
        $this->assertSame($this->warehouseId, $po->warehouseId);
        $this->assertSame('2031-01-05', $po->orderDate);
        $this->assertSame($this->userId, $po->createdBy);
        $this->assertSame([], $po->items);
    }

    public function test_find_by_id_returns_null_for_an_unknown_order(): void
    {
        $this->assertNull($this->repository->findById(999999999));
    }

    // ---------- item ----------

    public function test_added_items_are_loaded_with_the_order_in_insertion_order(): void
    {
        $po = $this->makePo();

        $this->repository->addItem($po->id, $this->productId, 10, 1250.5);
        $this->repository->addItem($po->id, $this->productId, 4, 900.0);

        $found = $this->repository->findById($po->id);
        $this->assertNotNull($found);
        $this->assertCount(2, $found->items);

        $first = $found->items[0];
        $this->assertSame($this->productId, $first->productId);
        $this->assertSame('PORepoTest Product', $first->productName);
        $this->assertSame(10, $first->qtyOrdered);
        $this->assertSame(0, $first->qtyReceived);
        $this->assertSame(1250.5, $first->buyPrice);
        $this->assertSame(10, $first->qtyRemaining());

        $this->assertSame(4, $found->items[1]->qtyOrdered);
        $this->assertSame(900.0, $found->items[1]->buyPrice);
    }

    // ---------- status ----------

    public function test_update_status_changes_the_stored_status(): void
    {
        $po = $this->makePo();

        $this->repository->updateStatus($po->id, 'Ordered');
        $this->assertSame('Ordered', $this->repository->findById($po->id)->status);

        $this->repository->updateStatus($po->id, 'Cancelled');
        $this->assertSame('Cancelled', $this->repository->findById($po->id)->status);
    }

    // ---------- findAll ----------

    public function test_find_all_lists_the_newest_order_date_first(): void
    {
        $older = $this->makePo('2031-01-01');
        $newer = $this->makePo('2031-03-01');

        $ids = array_map(static fn (PurchaseOrder $po): int => $po->id, $this->repository->findAll());

        $this->assertContains($older->id, $ids);
        $this->assertContains($newer->id, $ids);
        $this->assertGreaterThan(
            array_search($newer->id, $ids, true),
            array_search($older->id, $ids, true),
            'PO dengan tanggal lebih baru harus muncul lebih dulu',
        );
    }

    // ---------- search ----------

    public function test_search_matches_by_supplier_name(): void
    {
        $mine = $this->makePo();
        $this->makePo('2031-01-06', $this->otherSupplierId);

        $result = $this->repository->search('PORepoTest Alpha', null, 'date_desc', 1, 10);

        $this->assertSame([$mine->id], $this->idsOf($result));
        $this->assertSame(1, $result['total']);
    }

    public function test_search_matches_by_order_number(): void
    {
        $po = $this->makePo();

        $result = $this->repository->search((string) $po->id, null, 'date_desc', 1, 10);

        $this->assertContains($po->id, $this->idsOf($result));
    }

    public function test_search_returns_nothing_when_no_order_matches(): void
    {
        $this->makePo();

        $byText = $this->repository->search('PORepoTest-NoSuchSupplier', null, 'date_desc', 1, 10);
        $byNumber = $this->repository->search('999999999', null, 'date_desc', 1, 10);

        $this->assertSame(0, $byText['total']);
        $this->assertSame([], $byText['items']);
        $this->assertSame(0, $byNumber['total']);
    }

    public function test_search_filters_by_status(): void
    {
        $draft = $this->makePo('2031-01-05');
        $ordered = $this->makePo('2031-01-06');
        $this->repository->updateStatus($ordered->id, 'Ordered');

        $onlyOrdered = $this->repository->search('PORepoTest Alpha', 'Ordered', 'date_asc', 1, 10);
        $onlyDraft = $this->repository->search('PORepoTest Alpha', 'Draft', 'date_asc', 1, 10);
        $noStatusFilter = $this->repository->search('PORepoTest Alpha', '', 'date_asc', 1, 10);

        $this->assertSame([$ordered->id], $this->idsOf($onlyOrdered));
        $this->assertSame([$draft->id], $this->idsOf($onlyDraft));
        $this->assertSame(2, $noStatusFilter['total']);
    }

    public function test_search_sorts_by_order_date_in_both_directions(): void
    {
        $earlier = $this->makePo('2031-01-05');
        $later = $this->makePo('2031-03-05');

        $ascending = $this->repository->search('PORepoTest Alpha', null, 'date_asc', 1, 10);
        $descending = $this->repository->search('PORepoTest Alpha', null, 'date_desc', 1, 10);

        $this->assertSame([$earlier->id, $later->id], $this->idsOf($ascending));
        $this->assertSame([$later->id, $earlier->id], $this->idsOf($descending));
    }

    public function test_search_paginates_with_a_stable_total(): void
    {
        $this->makePo('2031-01-01');
        $this->makePo('2031-01-02');
        $this->makePo('2031-01-03');

        $first = $this->repository->search('PORepoTest Alpha', null, 'date_asc', 1, 2);
        $second = $this->repository->search('PORepoTest Alpha', null, 'date_asc', 2, 2);

        $this->assertSame(3, $first['total']);
        $this->assertSame(3, $second['total']);
        $this->assertCount(2, $first['items']);
        $this->assertCount(1, $second['items']);
        $this->assertSame([], array_values(array_intersect($this->idsOf($first), $this->idsOf($second))));
    }

    public function test_search_without_any_filter_includes_the_new_order(): void
    {
        $po = $this->makePo();

        $result = $this->repository->search(null, null, 'date_desc', 1, 1000);

        $this->assertContains($po->id, $this->idsOf($result));
        $this->assertGreaterThanOrEqual(1, $result['total']);
    }
}
