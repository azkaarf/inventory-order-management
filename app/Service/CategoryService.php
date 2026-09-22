<?php

namespace App\Service;

use App\Entity\Category;
use App\Repository\CategoryRepositoryInterface;

final class CategoryService
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
    ) {
    }

    /** @return Category[] */
    public function listCategories(): array
    {
        return $this->categoryRepository->findAll();
    }

    public function findById(int $id): ?Category
    {
        return $this->categoryRepository->findById($id);
    }

    /** @return string[] */
    public function validate(string $name): array
    {
        $errors = [];

        if (trim($name) === '') {
            $errors[] = 'Category name is required.';
        }

        return $errors;
    }

    public function createCategory(string $name, ?string $description): Category
    {
        return $this->categoryRepository->create($name, $description);
    }

    public function updateCategory(int $id, string $name, ?string $description): void
    {
        $this->categoryRepository->update($id, $name, $description);
    }

    /**
     * @return bool false if it failed because the category is still used by a product
     */
    public function deleteCategory(int $id): bool
    {
        return $this->categoryRepository->delete($id);
    }
}
