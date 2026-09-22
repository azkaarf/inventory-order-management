<?php

namespace App\Service;

use App\Entity\Warehouse;
use App\Repository\WarehouseRepositoryInterface;

final class WarehouseService
{
    public function __construct(
        private readonly WarehouseRepositoryInterface $warehouseRepository,
    ) {
    }

    /** @return Warehouse[] */
    public function listWarehouses(): array
    {
        return $this->warehouseRepository->findAll();
    }

    public function findById(int $id): ?Warehouse
    {
        return $this->warehouseRepository->findById($id);
    }

    /** @return string[] */
    public function validate(string $name, string $location): array
    {
        $errors = [];

        if (trim($name) === '') {
            $errors[] = 'Warehouse name is required.';
        }

        if (trim($location) === '') {
            $errors[] = 'Location is required.';
        }

        return $errors;
    }

    public function createWarehouse(string $name, string $location): Warehouse
    {
        return $this->warehouseRepository->create($name, $location);
    }

    public function updateWarehouse(int $id, string $name, string $location): void
    {
        $this->warehouseRepository->update($id, $name, $location);
    }

    public function setActive(int $id, bool $isActive): void
    {
        $this->warehouseRepository->setActive($id, $isActive);
    }
}
