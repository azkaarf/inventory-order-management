<?php

namespace App\Controller;

use App\Entity\Product;
use App\Service\CategoryService;
use App\Service\ProductService;
use App\Support\AuthGuard;
use App\Support\ImageUploader;
use InvalidArgumentException;

final class ProductController
{
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

        $result = $this->productService->searchProducts($q !== '' ? $q : null, $categoryId, $stockStatus, $page);
        $categories = $this->categoryService->listCategories();

        require __DIR__ . '/../../views/products/index.php';
    }

    public function show(): void
    {
        AuthGuard::requireLogin();

        $product = $this->findOrFail((int) ($_GET['id'] ?? 0));
        $stockBreakdown = $this->productService->stockBreakdown($product->id);
        $totalStock = $this->productService->totalStock($product->id);

        require __DIR__ . '/../../views/products/show.php';
    }

    public function showCreateForm(): void
    {
        AuthGuard::requireRole(['Admin']);

        $categories = $this->categoryService->listCategories();
        $errors = [];
        $old = $this->emptyFormData();

        require __DIR__ . '/../../views/products/create.php';
    }

    public function create(): void
    {
        AuthGuard::requireRole(['Admin']);

        $old = $this->readFormData();
        $errors = $this->productService->validate(
            $old['sku'],
            $old['name'],
            (int) $old['category_id'],
            $old['unit'],
            $old['buy_price'],
            $old['sell_price'],
            $old['reorder_point'],
        );

        $imagePath = null;
        if (empty($errors) && !empty($_FILES['image']['name'])) {
            try {
                $imagePath = ImageUploader::store($_FILES['image']);
            } catch (InvalidArgumentException $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (!empty($errors)) {
            $categories = $this->categoryService->listCategories();
            require __DIR__ . '/../../views/products/create.php';
            return;
        }

        $this->productService->createProduct(
            $old['sku'],
            $old['name'],
            (int) $old['category_id'],
            $old['unit'],
            (float) $old['buy_price'],
            (float) $old['sell_price'],
            (int) $old['reorder_point'],
            $imagePath,
        );

        header('Location: /products');
        exit;
    }

    public function showEditForm(): void
    {
        AuthGuard::requireRole(['Admin']);

        $product = $this->findOrFail((int) ($_GET['id'] ?? 0));
        $productId = $product->id;
        $categories = $this->categoryService->listCategories();
        $errors = [];
        $old = [
            'sku' => $product->sku,
            'name' => $product->name,
            'category_id' => (string) $product->categoryId,
            'unit' => $product->unit,
            'buy_price' => (string) $product->buyPrice,
            'sell_price' => (string) $product->sellPrice,
            'reorder_point' => (string) $product->reorderPoint,
        ];

        require __DIR__ . '/../../views/products/edit.php';
    }

    public function update(): void
    {
        AuthGuard::requireRole(['Admin']);

        $productId = (int) ($_POST['id'] ?? 0);
        $product = $this->findOrFail($productId);

        $old = $this->readFormData();
        $errors = $this->productService->validate(
            $product->sku,
            $old['name'],
            (int) $old['category_id'],
            $old['unit'],
            $old['buy_price'],
            $old['sell_price'],
            $old['reorder_point'],
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
            $categories = $this->categoryService->listCategories();
            require __DIR__ . '/../../views/products/edit.php';
            return;
        }

        $this->productService->updateProduct(
            $productId,
            $old['name'],
            (int) $old['category_id'],
            $old['unit'],
            (float) $old['buy_price'],
            (float) $old['sell_price'],
            (int) $old['reorder_point'],
            $imagePath,
        );

        header('Location: /products');
        exit;
    }

    public function toggleActive(): void
    {
        AuthGuard::requireRole(['Admin']);

        $id = (int) ($_POST['id'] ?? 0);
        $active = (int) ($_POST['active'] ?? 0) === 1;

        $this->productService->setActive($id, $active);

        header('Location: /products');
        exit;
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

    private function findOrFail(int $id): Product
    {
        $product = $this->productService->findById($id);

        if ($product === null) {
            http_response_code(404);
            echo '404 Not Found — product not found.';
            exit;
        }

        return $product;
    }
}
