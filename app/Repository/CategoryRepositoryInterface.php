<?php

namespace App\Repository;

use App\Entity\Category;

interface CategoryRepositoryInterface
{
    /** @return Category[] */
    public function findAll(): array;

    public function findById(int $id): ?Category;

    public function create(string $name, ?string $description): Category;

    public function update(int $id, string $name, ?string $description): void;

    /**
     * @return bool false if it failed because the category is still used by a product (FK constraint)
     */
    public function delete(int $id): bool;
}
