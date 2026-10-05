<?php

namespace App\Controller;

use App\Entity\Category;
use App\Service\CategoryService;
use App\Support\AuthGuard;

final class CategoryController extends BaseController
{
    private const INDEX_URL = '/categories';

    public function __construct(
        private readonly CategoryService $categoryService,
    ) {
    }

    public function index(): void
    {
        AuthGuard::requireRole(self::ROLE_ADMIN);

        $this->render('categories/index', [
            'categories' => $this->categoryService->listCategories(),
            'error' => $_GET['error'] ?? null,
        ]);
    }

    public function showCreateForm(): void
    {
        AuthGuard::requireRole(self::ROLE_ADMIN);

        $this->render('categories/create', [
            'errors' => [],
            'old' => ['name' => '', 'description' => ''],
        ]);
    }

    public function create(): void
    {
        AuthGuard::requireRole(self::ROLE_ADMIN);

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '') ?: null;

        $errors = $this->categoryService->validate($name);

        if (!empty($errors)) {
            $this->render('categories/create', [
                'errors' => $errors,
                'old' => ['name' => $name, 'description' => $description],
            ]);
            return;
        }

        $this->categoryService->createCategory($name, $description);
        $this->redirect(self::INDEX_URL);
    }

    public function showEditForm(): void
    {
        AuthGuard::requireRole(self::ROLE_ADMIN);

        $category = $this->findOrFail((int) ($_GET['id'] ?? 0));

        $this->render('categories/edit', [
            'category' => $category,
            'errors' => [],
            'old' => ['name' => $category->name, 'description' => $category->description],
        ]);
    }

    public function update(): void
    {
        AuthGuard::requireRole(self::ROLE_ADMIN);

        $id = (int) ($_POST['id'] ?? 0);
        $category = $this->findOrFail($id);

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '') ?: null;

        $errors = $this->categoryService->validate($name);

        if (!empty($errors)) {
            $this->render('categories/edit', [
                'category' => $category,
                'errors' => $errors,
                'old' => ['name' => $name, 'description' => $description],
            ]);
            return;
        }

        $this->categoryService->updateCategory($id, $name, $description);
        $this->redirect(self::INDEX_URL);
    }

    public function delete(): void
    {
        AuthGuard::requireRole(self::ROLE_ADMIN);

        $id = (int) ($_POST['id'] ?? 0);

        if (!$this->categoryService->deleteCategory($id)) {
            $this->redirect(self::INDEX_URL . '?error=in-use');
        }

        $this->redirect(self::INDEX_URL);
    }

    private function findOrFail(int $id): Category
    {
        $category = $this->categoryService->findById($id);

        if ($category === null) {
            $this->abort(404, '404 Not Found — category not found.');
        }

        return $category;
    }
}
