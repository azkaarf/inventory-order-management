<?php

namespace Tests\Integration;

use App\Dto\ProductData;
use App\Entity\SalesOrder;
use App\Repository\MySqlCategoryRepository;
use App\Repository\MySqlCustomerRepository;
use App\Repository\MySqlProductRepository;
use App\Repository\MySqlSalesOrderRepository;
use App\Repository\MySqlWarehouseRepository;
use App\Support\Database;
use PHPUnit\Framework\TestCase;

/**
 * TEST-02 (SO-01, FIND-01): query Sales Order terhadap MySQL nyata di database
 * inventory_test. Selain jalur goods issue yang sudah diuji di
 * GoodsIssueIntegrationTest, di sini diuji pembuatan SO, item, perubahan
 * status, approval, daftar, dan semua cabang pencarian - termasuk filter
 * pemilik ($createdBy) yang membatasi user Sales pada order miliknya sendiri.
 *
 * Semua data memakai customer dengan nama unik, jadi hasil pencarian tidak
 * bercampur dengan seed data, dan semuanya dibersihkan lagi di tearDown.
 */
final class SalesOrderRepositoryIntegrationTest extends TestCase
{
    private const CUSTOMER_NAME = 'SORepoTest Alpha Customer';

    private MySqlSalesOrderRepository $repository;
    private int $categoryId;
    private int $productId;
    private int $customerId;
    private int $otherCustomerId;
    private int $warehouseId;
    private int $userA;
    private int $userB;

    /** @var int[] */
    private array $soIds = [];

    protected function setUp(): void
    {
        $this->repository = new MySqlSalesOrderRepository();
        $pdo = Database::connection();

        $this->categoryId = (new MySqlCategoryRepository())->create('SORepoTest Category', null)->id;
        $this->productId = (new MySqlProductRepository())->create(
            'SORT-' . bin2hex(random_bytes(4)),
            new ProductData(
                name: 'SORepoTest Product',
                categoryId: $this->categoryId,
                unit: 'pcs',
                buyPrice: 1000.0,
                sellPrice: 1500.0,
                reorderPoint: 5,
                imagePath: null,
            ),
        )->id;

        $this->customerId = (new MySqlCustomerRepository())->create(self::CUSTOMER_NAME, null, null)->id;
        $this->otherCustomerId = (new MySqlCustomerRepository())->create('SORepoTest Beta Customer', null, null)->id;
        $this->warehouseId = (new MySqlWarehouseRepository())->create('SORepoTest Warehouse', 'Test City')->id;

        // Dua user berbeda dari seed: satu pembuat order, satu penyetuju.
        $this->userA = (int) $pdo->query('SELECT id FROM user ORDER BY id LIMIT 1')->fetchColumn();
        $this->userB = (int) $pdo->query('SELECT id FROM user ORDER BY id LIMIT 1 OFFSET 1')->fetchColumn();
    }

    protected function tearDown(): void
    {
        $pdo = Database::connection();

        // SO dulu (item ikut terhapus lewat ON DELETE CASCADE), baru produk
        // dan master data - produk dilindungi RESTRICT selama masih ada item.
        foreach ($this->soIds as $id) {
            $pdo->prepare('DELETE FROM sales_order WHERE id = ?')->execute([$id]);
        }
        $pdo->prepare('DELETE FROM product WHERE id = ?')->execute([$this->productId]);
        $pdo->prepare('DELETE FROM category WHERE id = ?')->execute([$this->categoryId]);
        $pdo->prepare('DELETE FROM customer WHERE id IN (?, ?)')->execute([$this->customerId, $this->otherCustomerId]);
        $pdo->prepare('DELETE FROM warehouse WHERE id = ?')->execute([$this->warehouseId]);
    }

    private function makeSo(string $orderDate = '2031-01-05', ?int $createdBy = null, ?int $customerId = null): SalesOrder
    {
        $so = $this->repository->create(
            $customerId ?? $this->customerId,
            $this->warehouseId,
            $orderDate,
            $createdBy ?? $this->userA,
        );
        $this->soIds[] = $so->id;

        return $so;
    }

    private function userName(int $userId): string
    {
        $stmt = Database::connection()->prepare('SELECT name FROM user WHERE id = ?');
        $stmt->execute([$userId]);

        return (string) $stmt->fetchColumn();
    }

    /**
     * @param array{items: SalesOrder[], total: int} $result
     * @return int[]
     */
    private function idsOf(array $result): array
    {
        return array_map(static fn (SalesOrder $so): int => $so->id, $result['items']);
    }

    // ---------- create / findById ----------

    public function test_create_returns_a_draft_order_with_the_joined_names(): void
    {
        $so = $this->makeSo('2031-01-05');

        $this->assertGreaterThan(0, $so->id);
        $this->assertSame('Draft', $so->status);
        $this->assertSame(self::CUSTOMER_NAME, $so->customerName);
        $this->assertSame('SORepoTest Warehouse', $so->warehouseName);
        $this->assertSame('2031-01-05', $so->orderDate);
        $this->assertSame($this->userA, $so->createdBy);
        $this->assertSame($this->userName($this->userA), $so->createdByName);
        $this->assertNull($so->approvedBy);
        $this->assertNull($so->approvedByName);
        $this->assertSame([], $so->items);
    }

    public function test_find_by_id_returns_null_for_an_unknown_order(): void
    {
        $this->assertNull($this->repository->findById(999999999));
    }

    // ---------- item ----------

    public function test_added_items_are_loaded_with_the_order_in_insertion_order(): void
    {
        $so = $this->makeSo();

        $this->repository->addItem($so->id, $this->productId, 7, 1500.0);
        $this->repository->addItem($so->id, $this->productId, 2, 1350.5);

        $found = $this->repository->findById($so->id);
        $this->assertNotNull($found);
        $this->assertCount(2, $found->items);

        $first = $found->items[0];
        $this->assertSame($this->productId, $first->productId);
        $this->assertSame('SORepoTest Product', $first->productName);
        $this->assertSame(7, $first->qty);
        $this->assertSame(1500.0, $first->sellPrice);

        $this->assertSame(2, $found->items[1]->qty);
        $this->assertSame(1350.5, $found->items[1]->sellPrice);
    }

    // ---------- status & approval ----------

    public function test_update_status_changes_the_stored_status(): void
    {
        $so = $this->makeSo();

        $this->repository->updateStatus($so->id, 'PendingApproval');
        $this->assertSame('PendingApproval', $this->repository->findById($so->id)->status);

        $this->repository->updateStatus($so->id, 'Cancelled');
        $this->assertSame('Cancelled', $this->repository->findById($so->id)->status);
    }

    public function test_approve_records_the_approver_and_marks_the_order_approved(): void
    {
        $so = $this->makeSo('2031-01-05', $this->userA);
        $this->repository->updateStatus($so->id, 'PendingApproval');

        $this->repository->approve($so->id, $this->userB);

        $approved = $this->repository->findById($so->id);
        $this->assertSame('Approved', $approved->status);
        $this->assertSame($this->userB, $approved->approvedBy);
        $this->assertSame($this->userName($this->userB), $approved->approvedByName);
        // Pembuat tetap pembuat asli, tidak tertimpa oleh penyetuju.
        $this->assertSame($this->userA, $approved->createdBy);
    }

    // ---------- findAll ----------

    public function test_find_all_lists_the_newest_order_date_first(): void
    {
        $older = $this->makeSo('2031-01-01');
        $newer = $this->makeSo('2031-03-01');

        $ids = array_map(static fn (SalesOrder $so): int => $so->id, $this->repository->findAll());

        $this->assertContains($older->id, $ids);
        $this->assertContains($newer->id, $ids);
        $this->assertGreaterThan(
            array_search($newer->id, $ids, true),
            array_search($older->id, $ids, true),
            'SO dengan tanggal lebih baru harus muncul lebih dulu',
        );
    }

    // ---------- search ----------

    public function test_search_matches_by_customer_name(): void
    {
        $mine = $this->makeSo();
        $this->makeSo('2031-01-06', null, $this->otherCustomerId);

        $result = $this->repository->search('SORepoTest Alpha', null, 'date_desc', 1, 10);

        $this->assertSame([$mine->id], $this->idsOf($result));
        $this->assertSame(1, $result['total']);
    }

    public function test_search_matches_by_order_number(): void
    {
        $so = $this->makeSo();

        $result = $this->repository->search((string) $so->id, null, 'date_desc', 1, 10);

        $this->assertContains($so->id, $this->idsOf($result));
    }

    public function test_search_returns_nothing_when_no_order_matches(): void
    {
        $this->makeSo();

        $byText = $this->repository->search('SORepoTest-NoSuchCustomer', null, 'date_desc', 1, 10);
        $byNumber = $this->repository->search('999999999', null, 'date_desc', 1, 10);

        $this->assertSame(0, $byText['total']);
        $this->assertSame([], $byText['items']);
        $this->assertSame(0, $byNumber['total']);
    }

    public function test_search_filters_by_status(): void
    {
        $draft = $this->makeSo('2031-01-05');
        $pending = $this->makeSo('2031-01-06');
        $this->repository->updateStatus($pending->id, 'PendingApproval');

        $onlyPending = $this->repository->search('SORepoTest Alpha', 'PendingApproval', 'date_asc', 1, 10);
        $onlyDraft = $this->repository->search('SORepoTest Alpha', 'Draft', 'date_asc', 1, 10);
        $noStatusFilter = $this->repository->search('SORepoTest Alpha', '', 'date_asc', 1, 10);

        $this->assertSame([$pending->id], $this->idsOf($onlyPending));
        $this->assertSame([$draft->id], $this->idsOf($onlyDraft));
        $this->assertSame(2, $noStatusFilter['total']);
    }

    public function test_search_restricts_results_to_the_owner_when_a_creator_is_given(): void
    {
        // SO-01: user Sales hanya melihat order buatannya sendiri.
        $byA = $this->makeSo('2031-01-05', $this->userA);
        $byB = $this->makeSo('2031-01-06', $this->userB);

        $forA = $this->repository->search('SORepoTest Alpha', null, 'date_asc', 1, 10, $this->userA);
        $forB = $this->repository->search('SORepoTest Alpha', null, 'date_asc', 1, 10, $this->userB);
        $forEveryone = $this->repository->search('SORepoTest Alpha', null, 'date_asc', 1, 10);

        $this->assertSame([$byA->id], $this->idsOf($forA));
        $this->assertSame([$byB->id], $this->idsOf($forB));
        $this->assertSame(2, $forEveryone['total']);
    }

    public function test_search_sorts_by_order_date_in_both_directions(): void
    {
        $earlier = $this->makeSo('2031-01-05');
        $later = $this->makeSo('2031-03-05');

        $ascending = $this->repository->search('SORepoTest Alpha', null, 'date_asc', 1, 10);
        $descending = $this->repository->search('SORepoTest Alpha', null, 'date_desc', 1, 10);

        $this->assertSame([$earlier->id, $later->id], $this->idsOf($ascending));
        $this->assertSame([$later->id, $earlier->id], $this->idsOf($descending));
    }

    public function test_search_paginates_with_a_stable_total(): void
    {
        $this->makeSo('2031-01-01');
        $this->makeSo('2031-01-02');
        $this->makeSo('2031-01-03');

        $first = $this->repository->search('SORepoTest Alpha', null, 'date_asc', 1, 2);
        $second = $this->repository->search('SORepoTest Alpha', null, 'date_asc', 2, 2);

        $this->assertSame(3, $first['total']);
        $this->assertSame(3, $second['total']);
        $this->assertCount(2, $first['items']);
        $this->assertCount(1, $second['items']);
        $this->assertSame([], array_values(array_intersect($this->idsOf($first), $this->idsOf($second))));
    }

    public function test_search_without_any_filter_includes_the_new_order(): void
    {
        $so = $this->makeSo();

        $result = $this->repository->search(null, null, 'date_desc', 1, 1000);

        $this->assertContains($so->id, $this->idsOf($result));
        $this->assertGreaterThanOrEqual(1, $result['total']);
    }
}
