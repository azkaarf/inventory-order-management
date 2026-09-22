<?php

namespace App\Service;

use App\Entity\Supplier;
use App\Repository\SupplierRepositoryInterface;

final class SupplierService
{
    public function __construct(
        private readonly SupplierRepositoryInterface $supplierRepository,
    ) {
    }

    /** @return Supplier[] */
    public function listSuppliers(): array
    {
        return $this->supplierRepository->findAll();
    }

    public function findById(int $id): ?Supplier
    {
        return $this->supplierRepository->findById($id);
    }

    /** @return string[] */
    public function validate(string $name): array
    {
        $errors = [];

        if (trim($name) === '') {
            $errors[] = 'Supplier name is required.';
        }

        return $errors;
    }

    public function createSupplier(string $name, ?string $contact, ?string $address): Supplier
    {
        return $this->supplierRepository->create($name, $contact, $address);
    }

    public function updateSupplier(int $id, string $name, ?string $contact, ?string $address): void
    {
        $this->supplierRepository->update($id, $name, $contact, $address);
    }

    public function setActive(int $id, bool $isActive): void
    {
        $this->supplierRepository->setActive($id, $isActive);
    }
}
