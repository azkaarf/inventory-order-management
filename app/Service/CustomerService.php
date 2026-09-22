<?php

namespace App\Service;

use App\Entity\Customer;
use App\Repository\CustomerRepositoryInterface;

final class CustomerService
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
    ) {
    }

    /** @return Customer[] */
    public function listCustomers(): array
    {
        return $this->customerRepository->findAll();
    }

    public function findById(int $id): ?Customer
    {
        return $this->customerRepository->findById($id);
    }

    /** @return string[] */
    public function validate(string $name): array
    {
        $errors = [];

        if (trim($name) === '') {
            $errors[] = 'Customer name is required.';
        }

        return $errors;
    }

    public function createCustomer(string $name, ?string $contact, ?string $address): Customer
    {
        return $this->customerRepository->create($name, $contact, $address);
    }

    public function updateCustomer(int $id, string $name, ?string $contact, ?string $address): void
    {
        $this->customerRepository->update($id, $name, $contact, $address);
    }

    public function setActive(int $id, bool $isActive): void
    {
        $this->customerRepository->setActive($id, $isActive);
    }
}
