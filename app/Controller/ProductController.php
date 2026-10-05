<?php

namespace App\Controller;

use App\Dto\ProductData;
use App\Entity\Product;
use App\Service\CategoryService;
use App\Service\ProductService;
use App\Support\AuthGuard;
use App\Support\CsvResponse;
use App\Support\ImageUploader;
use InvalidArgumentException;

final class ProductController extends BaseController
{
    private const INDEX_URL = '/products';

    public function __construct(
        private readonly ProductService $productService,
        private readonly CategoryService $categoryService,
    ) {
    }

    public function index(): void
    {
        AuthGuard::requireLogin();

        $q = trim($_GET['q'] ?? '');
        $categoryId = ($_GET['category_id'] ?? '') !== '' ? (int) $_GET['category_id'] : null;
        $stockStatus = in_array($_GET['stock_status'] ?? '', ['low', 'normal'], true) ? $_GET['stock_status'] : null;
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $this->render('products/index', [
            'q' => $q,
            'categoryId' => $categoryId,
            'stockStatus' => $stockStatus,
            'page' => $page,
            'result' => $this->productService->searchProducts($q !== '' ? $q : null, $categoryId, $stockStatus, $page),
            'categories' => $this->categoryService->listCategories(),
        ]);
    }

    public function show(): void
    {
        AuthGuard::requireLogin();

        $product = $this->findOrFail((int) ($_GET['id'] ?? 0));

        $this->render('products/show', [
            'product' => $product,
            'stockBreakdown' => $this->productService->stockBreakdown($product->id),
            'totalStock' => $this->productService->totalStock($product->id),
        ]);
    }

    public function showCreateForm(): void
    {
        AuthGuard::requireRole(self::ROLE_ADMIN);

        $this->render('products/create', [
            'categories' => $this->categoryService->listCategories(),
            'errors' => [],
            'old' => $this->emptyFormData(),
        ]);
    }

    public function create(): void
    {
        AuthGuard::requireRole(self::ROLE_ADMIN);

        $old = $this->readFormData();
        $errors = $this->productService->validate($old);

        $imagePath = null;
        if (empty($errors) && !empty($_FILES['image']['name'])) {
            try {
                $imagePath = ImageUploader::store($_FILES['image']);
            } catch (InvalidArgumentException $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (!empty($errors)) {
            $this->render('products/create', [
                'categories' => $this->categoryService->listCategories(),
                'errors' => $errors,
                'old' => $old,
            ]);
            return;
        }

        $this->productService->createProduct($old['sku'], $this->toProductData($old, $imagePath));

        $this->redirect(self::INDEX_URL);
    }

    public function showEditForm(): void
    {
        AuthGuard::requireRole(self::ROLE_ADMIN);

        $product = $this->findOrFail((int) ($_GET['id'] ?? 0));

        $this->render('products/edit', [
            'product' => $product,
            'productId' => $product->id,
            'categories' => $this->categoryService->listCategories(),
            'errors' => [],
            'old' => [
                'sku' => $product->sku,
                'name' => $product->name,
                'category_id' => (string) $product->categoryId,
                'unit' => $product->unit,
                'buy_price' => (string) $product->buyPrice,
                'sell_price' => (string) $product->sellPrice,
                'reorder_point' => (string) $product->reorderPoint,
            ],
        ]);
    }

    public function update(): void
    {
        AuthGuard::requireRole(self::ROLE_ADMIN);

        $productId = (int) ($_POST['id'] ?? 0);
        $product = $this->findOrFail($productId);

        $old = $this->readFormData();
        // SKU tidak bisa diubah saat edit, jadi validasi pakai SKU yang tersimpan
        $errors = $this->productService->validate(
            array_merge($old, ['sku' => $product->sku]),
            excludeId: $productId,
        );

        $imagePath = $product->imagePath;
        if (empty($errors) && !empty($_FILES['image']['name'])) {
            try {
                $imagePath = ImageUploader::store($_FILES['image']);
            } catch (InvalidArgumentException $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (!empty($errors)) {
            $this->render('products/edit', [
                'product' => $product,
                'productId' => $productId,
                'categories' => $this->categoryService->listCategories(),
                'errors' => $errors,
                'old' => $old,
            ]);
            return;
        }

        $this->productService->updateProduct($productId, $this->toProductData($old, $imagePath));

        $this->redirect(self::INDEX_URL);
    }

    public function toggleActive(): void
    {
        AuthGuard::requireRole(self::ROLE_ADMIN);

        $id = (int) ($_POST['id'] ?? 0);
        $active = (int) ($_POST['active'] ?? 0) === 1;

        $this->productService->setActive($id, $active);

        $this->redirect(self::INDEX_URL);
    }

    public function exportCsv(): void
    {
        AuthGuard::requireLogin();

        $q = trim($_GET['q'] ?? '');
        $categoryId = ($_GET['category_id'] ?? '') !== '' ? (int) $_GET['category_id'] : null;
        $stockStatus = in_array($_GET['stock_status'] ?? '', ['low', 'normal'], true) ? $_GET['stock_status'] : null;

        $rows = $this->productService->exportProducts($q !== '' ? $q : null, $categoryId, $stockStatus);

        CsvResponse::stream('products_' . date('Y-m-d') . '.csv', function ($out) use ($rows) {
            fputcsv($out, ['SKU', 'Name', 'Category', 'Unit', 'Sell Price', 'Total Stock', 'Reorder Point', 'Status']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row['sku'],
                    $row['name'],
                    $row['category_name'],
                    $row['unit'],
                    $row['sell_price'],
                    $row['total_stock'],
                    $row['reorder_point'],
                    $row['is_active'] ? 'Active' : 'Inactive',
                ]);
            }
        });
    }

    /** @return array<string,string> */
    private function readFormData(): array
    {
        return [
            'sku' => trim($_POST['sku'] ?? ''),
            'name' => trim($_POST['name'] ?? ''),
            'category_id' => trim($_POST['category_id'] ?? '0'),
            'unit' => trim($_POST['unit'] ?? ''),
            'buy_price' => trim($_POST['buy_price'] ?? ''),
            'sell_price' => trim($_POST['sell_price'] ?? ''),
            'reorder_point' => trim($_POST['reorder_point'] ?? '0'),
        ];
    }

    /** @return array<string,string> */
    private function emptyFormData(): array
    {
        return [
            'sku' => '',
            'name' => '',
            'category_id' => '',
            'unit' => '',
            'buy_price' => '',
            'sell_price' => '',
            'reorder_point' => '0',
        ];
    }

    /** @param array<string,string> $form */
    private function toProductData(array $form, ?string $imagePath): ProductData
    {
        return new ProductData(
            name: $form['name'],
            categoryId: (int) $form['category_id'],
            unit: $form['unit'],
            buyPrice: (float) $form['buy_price'],
            sellPrice: (float) $form['sell_price'],
            reorderPoint: (int) $form['reorder_point'],
            imagePath: $imagePath,
        );
    }

    private function findOrFail(int $id): Product
    {
        $product = $this->productService->findById($id);

        if ($product === null) {
            $this->abort(404, '404 Not Found — product not found.');
        }

        return $product;
    }
}
