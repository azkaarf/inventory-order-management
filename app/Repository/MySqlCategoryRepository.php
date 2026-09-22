<?php

namespace App\Repository;

use App\Entity\Category;
use App\Support\Database;
use PDOException;

final class MySqlCategoryRepository implements CategoryRepositoryInterface
{
    public function findAll(): array
    {
        $stmt = Database::connection()->query('SELECT id, name, description FROM category ORDER BY name');

        return array_map([$this, 'toEntity'], $stmt->fetchAll());
    }

    public function findById(int $id): ?Category
    {
        $stmt = Database::connection()->prepare('SELECT id, name, description FROM category WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? $this->toEntity($row) : null;
    }

    public function create(string $name, ?string $description): Category
    {
        $stmt = Database::connection()->prepare('INSERT INTO category (name, description) VALUES (?, ?)');
        $stmt->execute([$name, $description]);
        $id = (int) Database::connection()->lastInsertId();

        return new Category($id, $name, $description);
    }

    public function update(int $id, string $name, ?string $description): void
    {
        $stmt = Database::connection()->prepare('UPDATE category SET name = ?, description = ? WHERE id = ?');
        $stmt->execute([$name, $description, $id]);
    }

    public function delete(int $id): bool
    {
        try {
            $stmt = Database::connection()->prepare('DELETE FROM category WHERE id = ?');
            $stmt->execute([$id]);

            return true;
        } catch (PDOException $e) {
            // Category is still used by a product (foreign key constraint) — reject gracefully
            return false;
        }
    }

    private function toEntity(array $row): Category
    {
        return new Category((int) $row['id'], $row['name'], $row['description']);
    }
}
