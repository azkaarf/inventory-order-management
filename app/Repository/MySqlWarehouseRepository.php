<?php

namespace App\Repository;

use App\Entity\Warehouse;
use App\Support\Database;

final class MySqlWarehouseRepository implements WarehouseRepositoryInterface
{
    public function findAll(): array
    {
        $stmt = Database::connection()->query(
            'SELECT id, name, location, is_active FROM warehouse ORDER BY name'
        );

        return array_map([$this, 'toEntity'], $stmt->fetchAll());
    }

    public function findById(int $id): ?Warehouse
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, name, location, is_active FROM warehouse WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? $this->toEntity($row) : null;
    }

    public function create(string $name, string $location): Warehouse
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO warehouse (name, location, is_active) VALUES (?, ?, 1)'
        );
        $stmt->execute([$name, $location]);
        $id = (int) Database::connection()->lastInsertId();

        return new Warehouse($id, $name, $location, true);
    }

    public function update(int $id, string $name, string $location): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE warehouse SET name = ?, location = ? WHERE id = ?'
        );
        $stmt->execute([$name, $location, $id]);
    }

    public function setActive(int $id, bool $isActive): void
    {
        $stmt = Database::connection()->prepare('UPDATE warehouse SET is_active = ? WHERE id = ?');
        $stmt->execute([$isActive ? 1 : 0, $id]);
    }

    private function toEntity(array $row): Warehouse
    {
        return new Warehouse(
            id: (int) $row['id'],
            name: $row['name'],
            location: $row['location'],
            isActive: (bool) $row['is_active'],
        );
    }
}
