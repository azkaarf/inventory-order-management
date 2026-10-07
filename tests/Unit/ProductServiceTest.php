<?php

namespace Tests\Unit;

use App\Dto\ProductData;
use App\Entity\Category;
use App\Entity\Product;
use App\Repository\CategoryRepositoryInterface;
use App\Repository\ProductRepositoryInterface;
use App\Repository\StockRepositoryInterface;
use App\Service\ProductService;
use PHPUnit\Framework\TestCase;

/**
 * TEST-01: murni logika Service. Semua repository adalah mock/stub dari
 * interface-nya, jadi test ini tidak menyentuh database, session, atau
 * layanan eksternal - itulah gunanya Dependency Inversion di project ini.
 */
final class ProductServiceTest extends TestCase
{
    private function makeService(
        ?ProductRepositoryInterface $products = null,
        ?CategoryRepositoryInterface $categories = null,
        ?StockRepositoryInterface $stock = null,
    ): ProductService {
        return new ProductService(
            $products ?? $this->createStub(ProductRepositoryInterface::class),
            $categories ?? $this->createStub(CategoryRepositoryInterface::class),
            $stock ?? $this->createStub(StockRepositoryInterface::class),
        );
    }

    private function makeProduct(int $id = 7): Product
    {
        return new Product(
            id: $id,
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

    private function makeData(): ProductData
    {
        return new ProductData(
            name: 'Cleanser',
            categoryId: 3,
            unit: 'pcs',
            buyPrice: 1000.0,
            sellPrice: 1500.0,
            reorderPoint: 5,
            imagePath: null,
        );
    }

    /** @return array<string,string> */
    private function validForm(): array
    {
        return [
            'sku' => 'SKU-1',
            'name' => 'Cleanser',
            'category_id' => '3',
            'unit' => 'pcs',
            'buy_price' => '1000',
            'sell_price' => '1500',
            'reorder_point' => '5',
        ];
    }

    // ---------- validate() ----------

    public function test_validation_passes_for_a_complete_valid_form(): void
    {
        $products = $this->createStub(ProductRepositoryInterface::class);
        $products->method('skuExists')->willReturn(false);
        $categories = $this->createStub(CategoryRepositoryInterface::class);
        $categories->method('findById')->willReturn(new Category(3, 'Skincare', null));

        $errors = $this->makeService($products, $categories)->validate($this->validForm());

        $this->assertSame([], $errors);
    }

    public function test_validation_reports_every_required_field_for_an_empty_form(): void
    {
        $products = $this->createMock(ProductRepositoryInterface::class);
        // SKU kosong -> pengecekan unik ke database tidak boleh dijalankan sama sekali.
        $products->expects($this->never())->method('skuExists');

        $errors = $this->makeService($products)->validate([]);

        $this->assertContains('SKU is required.', $errors);
        $this->assertContains('Product name is required.', $errors);
        $this->assertContains('Invalid category.', $errors);
        $this->assertContains('Unit is required.', $errors);
        $this->assertContains('Buy price must be a number and cannot be negative.', $errors);
        $this->assertContains('Sell price must be a number and cannot be negative.', $errors);
        $this->assertContains('Reorder point must be a whole number and cannot be negative.', $errors);
        $this->assertCount(7, $errors);
    }

    public function test_validation_rejects_a_duplicate_sku_and_ignores_the_product_being_edited(): void
    {
        $products = $this->createMock(ProductRepositoryInterface::class);
        // Saat edit, id produk itu sendiri diteruskan supaya SKU-nya tidak dianggap duplikat.
        $products->expects($this->once())->method('skuExists')->with('SKU-1', 42)->willReturn(true);
        $categories = $this->createStub(CategoryRepositoryInterface::class);
        $categories->method('findById')->willReturn(new Category(3, 'Skincare', null));

        $errors = $this->makeService($products, $categories)->validate($this->validForm(), 42);

        $this->assertSame(['SKU is already used by another product.'], $errors);
    }

    public function test_validation_rejects_a_category_that_does_not_exist(): void
    {
        $categories = $this->createStub(CategoryRepositoryInterface::class);
        $categories->method('findById')->willReturn(null);

        $errors = $this->makeService(null, $categories)->validate($this->validForm());

        $this->assertSame(['Invalid category.'], $errors);
    }

    public function test_validation_rejects_negative_and_non_numeric_prices(): void
    {
        $categories = $this->createStub(CategoryRepositoryInterface::class);
        $categories->method('findById')->willReturn(new Category(3, 'Skincare', null));
        $form = ['buy_price' => '-1', 'sell_price' => 'abc'] + $this->validForm();

        $errors = $this->makeService(null, $categories)->validate($form);

        $this->assertContains('Buy price must be a number and cannot be negative.', $errors);
        $this->assertContains('Sell price must be a number and cannot be negative.', $errors);
    }

    public function test_validation_accepts_zero_but_rejects_invalid_reorder_points(): void
    {
        $categories = $this->createStub(CategoryRepositoryInterface::class);
        $categories->method('findById')->willReturn(new Category(3, 'Skincare', null));
        $service = $this->makeService(null, $categories);

        $this->assertSame([], $service->validate(['reorder_point' => '0'] + $this->validForm()));

        foreach (['-1', 'abc', '2.5', ''] as $invalid) {
            $errors = $service->validate(['reorder_point' => $invalid] + $this->validForm());
            $this->assertContains(
                'Reorder point must be a whole number and cannot be negative.',
                $errors,
                "Reorder point '{$invalid}' seharusnya ditolak",
            );
        }
    }

    // ---------- createProduct() / updateProduct() / setActive() ----------

    public function test_create_product_saves_it_then_initializes_stock_in_every_warehouse(): void
    {
        $data = $this->makeData();
        $product = $this->makeProduct(7);

        $products = $this->createMock(ProductRepositoryInterface::class);
        $products->expects($this->once())->method('create')->with('SKU-1', $data)->willReturn($product);
        $stock = $this->createMock(StockRepositoryInterface::class);
        // WH-01: produk baru langsung punya baris stok 0 di setiap gudang aktif.
        $stock->expects($this->once())->method('initializeForProduct')->with(7);

        $created = $this->makeService($products, null, $stock)->createProduct('SKU-1', $data);

        $this->assertSame($product, $created);
    }

    public function test_update_product_delegates_to_the_repository(): void
    {
        $data = $this->makeData();
        $products = $this->createMock(ProductRepositoryInterface::class);
        $products->expects($this->once())->method('update')->with(7, $data);

        $this->makeService($products)->updateProduct(7, $data);
    }

    public function test_set_active_delegates_to_the_repository(): void
    {
        $products = $this->createMock(ProductRepositoryInterface::class);
        $products->expects($this->once())->method('setActive')->with(7, false);

        $this->makeService($products)->setActive(7, false);
    }

    // ---------- search / export (FIND-01, REPORT-01) ----------

    public function test_search_computes_pagination_from_the_repository_total(): void
    {
        $products = $this->createMock(ProductRepositoryInterface::class);
        $products->expects($this->once())
            ->method('search')
            ->with('serum', 3, 'low', 2, 10)
            ->willReturn(['items' => [['id' => 1]], 'total' => 25]);

        $result = $this->makeService($products)->searchProducts('serum', 3, 'low', 2);

        $this->assertSame(2, $result['page']);
        $this->assertSame(10, $result['perPage']);
        $this->assertSame(25, $result['total']);
        $this->assertSame(3, $result['totalPages']);
        $this->assertSame([['id' => 1]], $result['items']);
    }

    public function test_search_clamps_a_page_below_one_and_never_reports_zero_pages(): void
    {
        $products = $this->createMock(ProductRepositoryInterface::class);
        $products->expects($this->once())
            ->method('search')
            ->with(null, null, null, 1, 10)
            ->willReturn(['items' => [], 'total' => 0]);

        $result = $this->makeService($products)->searchProducts(null, null, null, -5);

        $this->assertSame(1, $result['page']);
        $this->assertSame(1, $result['totalPages']);
    }

    public function test_export_uses_the_same_search_without_the_page_cap(): void
    {
        $products = $this->createMock(ProductRepositoryInterface::class);
        $products->expects($this->once())
            ->method('search')
            ->with('serum', null, null, 1, 10000)
            ->willReturn(['items' => [['id' => 1], ['id' => 2]], 'total' => 2]);

        $rows = $this->makeService($products)->exportProducts('serum', null, null);

        $this->assertSame([['id' => 1], ['id' => 2]], $rows);
    }

    // ---------- pass-through reads ----------

    public function test_read_methods_return_what_the_repositories_provide(): void
    {
        $product = $this->makeProduct(7);
        $products = $this->createStub(ProductRepositoryInterface::class);
        $products->method('findAll')->willReturn([$product]);
        $products->method('findById')->willReturn($product);
        $stock = $this->createStub(StockRepositoryInterface::class);
        $stock->method('getBreakdownForProduct')->willReturn([['warehouse_name' => 'Jakarta', 'quantity' => 4]]);
        $stock->method('getTotalForProduct')->willReturn(4);

        $service = $this->makeService($products, null, $stock);

        $this->assertSame([$product], $service->listProducts());
        $this->assertSame($product, $service->findById(7));
        $this->assertSame([['warehouse_name' => 'Jakarta', 'quantity' => 4]], $service->stockBreakdown(7));
        $this->assertSame(4, $service->totalStock(7));
    }
}
