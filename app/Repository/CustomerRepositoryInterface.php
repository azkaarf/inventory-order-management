<?php

namespace App\Repository;

use App\Entity\Customer;

interface CustomerRepositoryInterface
{
    /** @return Customer[] */
    public function findAll(): array;

    public function findById(int $id): ?Customer;

    public function create(string $name, ?string $contact, ?string $address): Customer;

    public function update(int $id, string $name, ?string $contact, ?string $address): void;

    public function setActive(int $id, bool $isActive): void;
}
