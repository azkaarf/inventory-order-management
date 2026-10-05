<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Service\CustomerService;
use App\Support\AuthGuard;

final class CustomerController extends BaseController
{
    private const INDEX_URL = '/customers';

    public function __construct(
        private readonly CustomerService $customerService,
    ) {
    }

    public function index(): void
    {
        AuthGuard::requireRole(self::ROLE_ADMIN);

        $this->render('customers/index', [
            'customers' => $this->customerService->listCustomers(),
        ]);
    }

    public function showCreateForm(): void
    {
        AuthGuard::requireRole(self::ROLE_ADMIN);

        $this->render('customers/create', [
            'errors' => [],
            'old' => ['name' => '', 'contact' => '', 'address' => ''],
        ]);
    }

    public function create(): void
    {
        AuthGuard::requireRole(self::ROLE_ADMIN);

        $name = trim($_POST['name'] ?? '');
        $contact = trim($_POST['contact'] ?? '') ?: null;
        $address = trim($_POST['address'] ?? '') ?: null;

        $errors = $this->customerService->validate($name);

        if (!empty($errors)) {
            $this->render('customers/create', [
                'errors' => $errors,
                'old' => ['name' => $name, 'contact' => $contact, 'address' => $address],
            ]);
            return;
        }

        $this->customerService->createCustomer($name, $contact, $address);
        $this->redirect(self::INDEX_URL);
    }

    public function showEditForm(): void
    {
        AuthGuard::requireRole(self::ROLE_ADMIN);

        $customer = $this->findOrFail((int) ($_GET['id'] ?? 0));

        $this->render('customers/edit', [
            'customer' => $customer,
            'errors' => [],
            'old' => ['name' => $customer->name, 'contact' => $customer->contact, 'address' => $customer->address],
        ]);
    }

    public function update(): void
    {
        AuthGuard::requireRole(self::ROLE_ADMIN);

        $id = (int) ($_POST['id'] ?? 0);
        $customer = $this->findOrFail($id);

        $name = trim($_POST['name'] ?? '');
        $contact = trim($_POST['contact'] ?? '') ?: null;
        $address = trim($_POST['address'] ?? '') ?: null;

        $errors = $this->customerService->validate($name);

        if (!empty($errors)) {
            $this->render('customers/edit', [
                'customer' => $customer,
                'errors' => $errors,
                'old' => ['name' => $name, 'contact' => $contact, 'address' => $address],
            ]);
            return;
        }

        $this->customerService->updateCustomer($id, $name, $contact, $address);
        $this->redirect(self::INDEX_URL);
    }

    public function toggleActive(): void
    {
        AuthGuard::requireRole(self::ROLE_ADMIN);

        $id = (int) ($_POST['id'] ?? 0);
        $active = (int) ($_POST['active'] ?? 0) === 1;

        $this->customerService->setActive($id, $active);

        $this->redirect(self::INDEX_URL);
    }

    private function findOrFail(int $id): Customer
    {
        $customer = $this->customerService->findById($id);

        if ($customer === null) {
            $this->abort(404, '404 Not Found — customer not found.');
        }

        return $customer;
    }
}
