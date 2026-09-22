<?php

namespace App\Controller;

use App\Entity\Category;
use App\Service\CategoryService;
use App\Support\AuthGuard;

final class CategoryController
{
    public function __construct(
        private readonly CategoryService $categoryService,
    ) {
    }

    public function index(): void
    {
        AuthGuard::requireRole(['Admin']);

        $categories = $this->categoryService->listCategories();
        $error = $_GET['error'] ?? null;
        require __DIR__ . '/../../views/categories/index.php';
    }

    public function showCreateForm(): void
    {
        AuthGuard::requireRole(['Admin']);

        $errors = [];
        $old = ['name' => '', 'description' => ''];
        require __DIR__ . '/../../views/categories/create.php';
    }

    public function create(): void
    {
        AuthGuard::requireRole(['Admin']);

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '') ?: null;

        $errors = $this->categoryService->validate($name);

        if (!empty($errors)) {
            $old = ['name' => $name, 'description' => $description];
            require __DIR__ . '/../../views/categories/create.php';
            return;
        }

        $this->categoryService->createCategory($name, $description);
        header('Location: /categories');
        exit;
    }

    public function showEditForm(): void
    {
        AuthGuard::requireRole(['Admin']);

        $category = $this->findOrFail((int) ($_GET['id'] ?? 0));
        $errors = [];
        $old = ['name' => $category->name, 'description' => $category->description];
        require __DIR__ . '/../../views/categories/edit.php';
    }

    public function update(): void
    {
        AuthGuard::requireRole(['Admin']);

        $id = (int) ($_POST['id'] ?? 0);
        $this->findOrFail($id);

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '') ?: null;

        $errors = $this->categoryService->validate($name);

        if (!empty($errors)) {
            $old = ['name' => $name, 'description' => $description];
            require __DIR__ . '/../../views/categories/edit.php';
            return;
        }

        $this->categoryService->updateCategory($id, $name, $description);
        header('Location: /categories');
        exit;
    }

    public function delete(): void
    {
        AuthGuard::requireRole(['Admin']);

        $id = (int) ($_POST['id'] ?? 0);
        $success = $this->categoryService->deleteCategory($id);

        if (!$success) {
            header('Location: /categories?error=in-use');
            exit;
        }

        header('Location: /categories');
        exit;
    }

    private function findOrFail(int $id): Category
    {
        $category = $this->categoryService->findById($id);

        if ($category === null) {
            http_response_code(404);
            echo '404 Not Found — category not found.';
            exit;
        }

        return $category;
    }
}
