<?php

namespace Tests\Integration;

use App\Dto\ProductData;
use App\Entity\Product;
use App\Repository\MySqlCategoryRepository;
use App\Repository\MySqlProductRepository;
use App\Support\Database;
use PHPUnit\Framework\TestCase;

/**
 * TEST-02: menyentuh MySQL nyata (database inventory_test, lihat phpunit.xml).
 * Memverifikasi query Product - find, SKU unik, update, aktif/nonaktif, dan
 * search/filter/pagination (FIND-01) - benar-benar jalan terhadap database,
 * bukan hanya logika di memori.
 */
final class ProductRepositoryIntegrationTest extends TestCase
{
    private MySqlProductRepository $repository;
    private int $categoryId;

    /** @var int[] */
    private array $productIds = [];

    protected function setUp(): void
    {
        $this->repository = new MySqlProductRepository();
        $this->categoryId = (new MySqlCategoryRepository())->create('PRD Integration Category', null)->id;
    }

    protected function tearDown(): void
    {
        $pdo = Database::connection();

        foreach ($this->productIds as $id) {
            $pdo->prepare('DELETE FROM product_stock WHERE product_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM product WHERE id = ?')->execute([$id]);
        }

        $pdo->prepare('DELETE FROM category WHERE id = ?')->execute([$this->categoryId]);
    }

    private function makeProduct(string $name = 'PRD Integration Product', int $reorderPoint = 5): Product
    {
        $product = $this->repository->create(
            'PRD-TEST-' . bin2hex(random_bytes(4)),
            new ProductData(
                name: $name,
                categoryId: $this->categoryId,
                unit: 'pcs',
                buyPrice: 1000.0,
                sellPrice: 1500.0,
                reorderPoint: $reorderPoint,
                imagePath: null,
            ),
        );
        $this->productIds[] = $product->id;

        return $product;
    }

    /**
     * @param array{items: array<int, array<string, mixed>>, total: int} $result
     * @return int[]
     */
    private function idsOf(array $result): array
    {
        return array_map('intval', array_column($result['items'], 'id'));
    }

    public function test_create_persists_a_new_active_product(): void
    {
        $product = $this->makeProduct('PRD Created');

        $this->assertGreaterThan(0, $product->id);
        $this->assertTrue($product->isActive);

        $found = $this->repository->findById($product->id);
        $this->assertNotNull($found);
        $this->assertSame('PRD Created', $found->name);
        $this->assertSame($this->categoryId, $found->categoryId);
        $this->assertSame(1000.0, $found->buyPrice);
        $this->assertSame(1500.0, $found->sellPrice);
        $this->assertSame(5, $found->reorderPoint);
        $this->assertNull($found->imagePath);
    }

    public function test_find_returns_null_for_unknown_id_and_sku(): void
    {
        $this->assertNull($this->repository->findById(999999999));
        $this->assertNull($this->repository->findBySku('PRD-DOES-NOT-EXIST'));
    }

    public function test_find_by_sku_returns_the_matching_product(): void
    {
        $product = $this->makeProduct();

        $found = $this->repository->findBySku($product->sku);

        $this->assertNotNull($found);
        $this->assertSame($product->id, $found->id);
    }

    public function test_sku_exists_respects_the_excluded_id(): void
    {
        $product = $this->makeProduct();

        $this->assertTrue($this->repository->skuExists($product->sku));
        $this->assertFalse($this->repository->skuExists($product->sku, $product->id));
        $this->assertFalse($this->repository->skuExists('PRD-DOES-NOT-EXIST'));
    }

    public function test_update_changes_the_editable_fields_but_not_the_sku(): void
    {
        $product = $this->makeProduct();

        $this->repository->update($product->id, new ProductData(
            name: 'PRD Renamed',
            categoryId: $this->categoryId,
            unit: 'box',
            buyPrice: 2000.0,
            sellPrice: 2500.0,
            reorderPoint: 9,
            imagePath: 'uploads/products/example.png',
        ));

        $found = $this->repository->findById($product->id);
        $this->assertNotNull($found);
        $this->assertSame('PRD Renamed', $found->name);
        $this->assertSame('box', $found->unit);
        $this->assertSame(2000.0, $found->buyPrice);
        $this->assertSame(2500.0, $found->sellPrice);
        $this->assertSame(9, $found->reorderPoint);
        $this->assertSame('uploads/products/example.png', $found->imagePath);
        $this->assertSame($product->sku, $found->sku);
    }

    public function test_set_active_deactivates_and_reactivates_instead_of_deleting(): void
    {
        $product = $this->makeProduct();

        $this->repository->setActive($product->id, false);
        $this->assertFalse($this->repository->findById($product->id)->isActive);

        $this->repository->setActive($product->id, true);
        $this->assertTrue($this->repository->findById($product->id)->isActive);
    }

    public function test_find_all_includes_the_created_product(): void
    {
        $product = $this->makeProduct();

        $ids = array_map(static fn (Product $p): int => $p->id, $this->repository->findAll());

        $this->assertContains($product->id, $ids);
    }

    public function test_search_matches_by_sku_and_name(): void
    {
        $product = $this->makeProduct('PRD Searchable Unique Name');

        $bySku = $this->repository->search($product->sku, null, null, 1, 10);
        $this->assertSame([$product->id], $this->idsOf($bySku));

        $byName = $this->repository->search('Searchable Unique', null, null, 1, 10);
        $this->assertContains($product->id, $this->idsOf($byName));

        $none = $this->repository->search('PRD-NO-MATCH-' . bin2hex(random_bytes(4)), null, null, 1, 10);
        $this->assertSame(0, $none['total']);
    }

    public function test_search_filters_by_category(): void
    {
        $this->makeProduct('PRD Cat A');
        $this->makeProduct('PRD Cat B');

        $result = $this->repository->search(null, $this->categoryId, null, 1, 10);

        $this->assertSame(2, $result['total']);
    }

    public function test_search_filters_low_and_normal_stock(): void
    {
        // Tanpa baris stok: total_stock = 0.
        $low = $this->makeProduct('PRD Low Stock', 5);      // 0 < 5  -> low
        $normal = $this->makeProduct('PRD Normal Stock', 0); // 0 >= 0 -> normal

        $lowIds = $this->idsOf($this->repository->search(null, $this->categoryId, 'low', 1, 10));
        $normalIds = $this->idsOf($this->repository->search(null, $this->categoryId, 'normal', 1, 10));

        $this->assertSame([$low->id], $lowIds);
        $this->assertSame([$normal->id], $normalIds);
    }

    public function test_search_paginates_results(): void
    {
        $this->makeProduct('PRD Page 1');
        $this->makeProduct('PRD Page 2');
        $this->makeProduct('PRD Page 3');

        $firstPage = $this->repository->search(null, $this->categoryId, null, 1, 2);
        $secondPage = $this->repository->search(null, $this->categoryId, null, 2, 2);

        $this->assertSame(3, $firstPage['total']);
        $this->assertCount(2, $firstPage['items']);
        $this->assertCount(1, $secondPage['items']);
    }
}
