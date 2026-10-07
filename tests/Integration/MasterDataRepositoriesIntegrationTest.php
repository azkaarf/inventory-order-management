<?php

namespace Tests\Integration;

use App\Dto\ProductData;
use App\Repository\MySqlCategoryRepository;
use App\Repository\MySqlCustomerRepository;
use App\Repository\MySqlProductRepository;
use App\Repository\MySqlSupplierRepository;
use App\Repository\MySqlWarehouseRepository;
use App\Support\Database;
use PHPUnit\Framework\TestCase;

/**
 * TEST-02: CRUD master data (kategori, customer, supplier, gudang) terhadap
 * MySQL nyata di database inventory_test. Data yang dibuat dibersihkan lagi
 * di tearDown, jadi seed data tidak terganggu.
 */
final class MasterDataRepositoriesIntegrationTest extends TestCase
{
    /** @var array<string, int[]> */
    private array $created = [
        'product' => [],
        'category' => [],
        'customer' => [],
        'supplier' => [],
        'warehouse' => [],
    ];

    protected function tearDown(): void
    {
        $pdo = Database::connection();

        // product dulu: category dilindungi ON DELETE RESTRICT selama masih dipakai produk.
        foreach ($this->created['product'] as $id) {
            $pdo->prepare('DELETE FROM product_stock WHERE product_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM product WHERE id = ?')->execute([$id]);
        }

        foreach (['category', 'customer', 'supplier', 'warehouse'] as $table) {
            foreach ($this->created[$table] as $id) {
                $pdo->prepare("DELETE FROM {$table} WHERE id = ?")->execute([$id]);
            }
        }
    }

    private function track(string $table, int $id): int
    {
        $this->created[$table][] = $id;

        return $id;
    }

    /**
     * @param object[] $entities
     * @return int[]
     */
    private function idsOf(array $entities): array
    {
        return array_map(static fn (object $e): int => $e->id, $entities);
    }

    // ---------- Category ----------

    public function test_category_can_be_created_found_listed_updated_and_deleted(): void
    {
        $repo = new MySqlCategoryRepository();
        $category = $repo->create('MD Category', 'Initial description');
        $id = $this->track('category', $category->id);

        $found = $repo->findById($id);
        $this->assertNotNull($found);
        $this->assertSame('MD Category', $found->name);
        $this->assertSame('Initial description', $found->description);
        $this->assertContains($id, $this->idsOf($repo->findAll()));

        $repo->update($id, 'MD Category Renamed', null);
        $updated = $repo->findById($id);
        $this->assertSame('MD Category Renamed', $updated->name);
        $this->assertNull($updated->description);

        $this->assertTrue($repo->delete($id));
        $this->assertNull($repo->findById($id));
    }

    public function test_category_delete_is_rejected_while_a_product_still_uses_it(): void
    {
        $categoryRepo = new MySqlCategoryRepository();
        $category = $categoryRepo->create('MD Category In Use', null);
        $this->track('category', $category->id);

        $product = (new MySqlProductRepository())->create(
            'MD-TEST-' . bin2hex(random_bytes(4)),
            new ProductData(
                name: 'MD Product',
                categoryId: $category->id,
                unit: 'pcs',
                buyPrice: 100.0,
                sellPrice: 150.0,
                reorderPoint: 1,
                imagePath: null,
            ),
        );
        $this->track('product', $product->id);

        // ON DELETE RESTRICT: kategori yang dipakai produk tidak boleh terhapus.
        $this->assertFalse($categoryRepo->delete($category->id));
        $this->assertNotNull($categoryRepo->findById($category->id));
    }

    public function test_category_find_by_id_returns_null_for_an_unknown_id(): void
    {
        $this->assertNull((new MySqlCategoryRepository())->findById(999999999));
    }

    // ---------- Customer ----------

    public function test_customer_can_be_created_found_listed_updated_and_toggled(): void
    {
        $repo = new MySqlCustomerRepository();
        $customer = $repo->create('MD Customer', '0812-0000', 'Jakarta');
        $id = $this->track('customer', $customer->id);

        $this->assertTrue($customer->isActive);
        $found = $repo->findById($id);
        $this->assertNotNull($found);
        $this->assertSame('MD Customer', $found->name);
        $this->assertSame('0812-0000', $found->contact);
        $this->assertSame('Jakarta', $found->address);
        $this->assertContains($id, $this->idsOf($repo->findAll()));

        $repo->update($id, 'MD Customer Renamed', null, null);
        $updated = $repo->findById($id);
        $this->assertSame('MD Customer Renamed', $updated->name);
        $this->assertNull($updated->contact);
        $this->assertNull($updated->address);

        $repo->setActive($id, false);
        $this->assertFalse($repo->findById($id)->isActive);
        $repo->setActive($id, true);
        $this->assertTrue($repo->findById($id)->isActive);
    }

    public function test_customer_find_by_id_returns_null_for_an_unknown_id(): void
    {
        $this->assertNull((new MySqlCustomerRepository())->findById(999999999));
    }

    // ---------- Supplier ----------

    public function test_supplier_can_be_created_found_listed_updated_and_toggled(): void
    {
        $repo = new MySqlSupplierRepository();
        $supplier = $repo->create('MD Supplier', '021-0000', 'Bekasi');
        $id = $this->track('supplier', $supplier->id);

        $this->assertTrue($supplier->isActive);
        $found = $repo->findById($id);
        $this->assertNotNull($found);
        $this->assertSame('MD Supplier', $found->name);
        $this->assertSame('021-0000', $found->contact);
        $this->assertSame('Bekasi', $found->address);
        $this->assertContains($id, $this->idsOf($repo->findAll()));

        $repo->update($id, 'MD Supplier Renamed', null, null);
        $updated = $repo->findById($id);
        $this->assertSame('MD Supplier Renamed', $updated->name);
        $this->assertNull($updated->contact);

        $repo->setActive($id, false);
        $this->assertFalse($repo->findById($id)->isActive);
        $repo->setActive($id, true);
        $this->assertTrue($repo->findById($id)->isActive);
    }

    public function test_supplier_find_by_id_returns_null_for_an_unknown_id(): void
    {
        $this->assertNull((new MySqlSupplierRepository())->findById(999999999));
    }

    // ---------- Warehouse ----------

    public function test_warehouse_can_be_created_found_listed_updated_and_toggled(): void
    {
        $repo = new MySqlWarehouseRepository();
        $warehouse = $repo->create('MD Warehouse', 'Bandung');
        $id = $this->track('warehouse', $warehouse->id);

        $this->assertTrue($warehouse->isActive);
        $found = $repo->findById($id);
        $this->assertNotNull($found);
        $this->assertSame('MD Warehouse', $found->name);
        $this->assertSame('Bandung', $found->location);
        $this->assertContains($id, $this->idsOf($repo->findAll()));

        $repo->update($id, 'MD Warehouse Renamed', 'Semarang');
        $updated = $repo->findById($id);
        $this->assertSame('MD Warehouse Renamed', $updated->name);
        $this->assertSame('Semarang', $updated->location);

        $repo->setActive($id, false);
        $this->assertFalse($repo->findById($id)->isActive);
        $repo->setActive($id, true);
        $this->assertTrue($repo->findById($id)->isActive);
    }

    public function test_warehouse_find_by_id_returns_null_for_an_unknown_id(): void
    {
        $this->assertNull((new MySqlWarehouseRepository())->findById(999999999));
    }
}
