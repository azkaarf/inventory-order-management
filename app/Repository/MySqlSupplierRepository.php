<?php

namespace App\Repository;

use App\Entity\Supplier;
use App\Support\Database;

final class MySqlSupplierRepository implements SupplierRepositoryInterface
{
    public function findAll(): array
    {
        $stmt = Database::connection()->query(
            'SELECT id, name, contact, address, is_active FROM supplier ORDER BY name'
        );

        return array_map([$this, 'toEntity'], $stmt->fetchAll());
    }

    public function findById(int $id): ?Supplier
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, name, contact, address, is_active FROM supplier WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? $this->toEntity($row) : null;
    }

    public function create(string $name, ?string $contact, ?string $address): Supplier
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO supplier (name, contact, address, is_active) VALUES (?, ?, ?, 1)'
        );
        $stmt->execute([$name, $contact, $address]);
        $id = (int) Database::connection()->lastInsertId();

        return new Supplier($id, $name, $contact, $address, true);
    }

    public function update(int $id, string $name, ?string $contact, ?string $address): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE supplier SET name = ?, contact = ?, address = ? WHERE id = ?'
        );
        $stmt->execute([$name, $contact, $address, $id]);
    }

    public function setActive(int $id, bool $isActive): void
    {
        $stmt = Database::connection()->prepare('UPDATE supplier SET is_active = ? WHERE id = ?');
        $stmt->execute([$isActive ? 1 : 0, $id]);
    }

    private function toEntity(array $row): Supplier
    {
        return new Supplier(
            id: (int) $row['id'],
            name: $row['name'],
            contact: $row['contact'],
            address: $row['address'],
            isActive: (bool) $row['is_active'],
        );
    }
}
