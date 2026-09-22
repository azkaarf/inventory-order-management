<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Service\CustomerService;
use App\Support\AuthGuard;

final class CustomerController
{
    public function __construct(
        private readonly CustomerService $customerService,
    ) {
    }

    public function index(): void
    {
        AuthGuard::requireRole(['Admin']);

        $customers = $this->customerService->listCustomers();
        require __DIR__ . '/../../views/customers/index.php';
    }

    public function showCreateForm(): void
    {
        AuthGuard::requireRole(['Admin']);

        $errors = [];
        $old = ['name' => '', 'contact' => '', 'address' => ''];
        require __DIR__ . '/../../views/customers/create.php';
    }

    public function create(): void
    {
        AuthGuard::requireRole(['Admin']);

        $name = trim($_POST['name'] ?? '');
        $contact = trim($_POST['contact'] ?? '') ?: null;
        $address = trim($_POST['address'] ?? '') ?: null;

        $errors = $this->customerService->validate($name);

        if (!empty($errors)) {
            $old = ['name' => $name, 'contact' => $contact, 'address' => $address];
            require __DIR__ . '/../../views/customers/create.php';
            return;
        }

        $this->customerService->createCustomer($name, $contact, $address);
        header('Location: /customers');
        exit;
    }

    public function showEditForm(): void
    {
        AuthGuard::requireRole(['Admin']);

        $customer = $this->findOrFail((int) ($_GET['id'] ?? 0));
        $errors = [];
        $old = ['name' => $customer->name, 'contact' => $customer->contact, 'address' => $customer->address];
        require __DIR__ . '/../../views/customers/edit.php';
    }

    public function update(): void
    {
        AuthGuard::requireRole(['Admin']);

        $id = (int) ($_POST['id'] ?? 0);
        $this->findOrFail($id);

        $name = trim($_POST['name'] ?? '');
        $contact = trim($_POST['contact'] ?? '') ?: null;
        $address = trim($_POST['address'] ?? '') ?: null;

        $errors = $this->customerService->validate($name);

        if (!empty($errors)) {
            $old = ['name' => $name, 'contact' => $contact, 'address' => $address];
            require __DIR__ . '/../../views/customers/edit.php';
            return;
        }

        $this->customerService->updateCustomer($id, $name, $contact, $address);
        header('Location: /customers');
        exit;
    }

    public function toggleActive(): void
    {
        AuthGuard::requireRole(['Admin']);

        $id = (int) ($_POST['id'] ?? 0);
        $active = (int) ($_POST['active'] ?? 0) === 1;

        $this->customerService->setActive($id, $active);

        header('Location: /customers');
        exit;
    }

    private function findOrFail(int $id): Customer
    {
        $customer = $this->customerService->findById($id);

        if ($customer === null) {
            http_response_code(404);
            echo '404 Not Found — customer not found.';
            exit;
        }

        return $customer;
    }
}
