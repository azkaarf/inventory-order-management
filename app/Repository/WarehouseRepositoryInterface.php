<?php

namespace App\Repository;

use App\Entity\Warehouse;

interface WarehouseRepositoryInterface
{
    /** @return Warehouse[] */
    public function findAll(): array;

    public function findById(int $id): ?Warehouse;

    public function create(string $name, string $location): Warehouse;

    public function update(int $id, string $name, string $location): void;

    public function setActive(int $id, bool $isActive): void;
}
