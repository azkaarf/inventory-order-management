<?php

namespace App\Repository;

use App\Entity\Supplier;

interface SupplierRepositoryInterface
{
    /** @return Supplier[] */
    public function findAll(): array;

    public function findById(int $id): ?Supplier;

    public function create(string $name, ?string $contact, ?string $address): Supplier;

    public function update(int $id, string $name, ?string $contact, ?string $address): void;

    public function setActive(int $id, bool $isActive): void;
}
