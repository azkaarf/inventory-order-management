<?php

namespace App\Controller;

use App\Entity\Supplier;
use App\Service\SupplierService;
use App\Support\AuthGuard;

final class SupplierController extends BaseController
{
    private const INDEX_URL = '/suppliers';

    public function __construct(
        private readonly SupplierService $supplierService,
    ) {
    }

    public function index(): void
    {
        AuthGuard::requireRole(self::ROLE_ADMIN);

        $this->render('suppliers/index', [
            'suppliers' => $this->supplierService->listSuppliers(),
        ]);
    }

    public function showCreateForm(): void
    {
        AuthGuard::requireRole(self::ROLE_ADMIN);

        $this->render('suppliers/create', [
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

        $errors = $this->supplierService->validate($name);

        if (!empty($errors)) {
            $this->render('suppliers/create', [
                'errors' => $errors,
                'old' => ['name' => $name, 'contact' => $contact, 'address' => $address],
            ]);
            return;
        }

        $this->supplierService->createSupplier($name, $contact, $address);
        $this->redirect(self::INDEX_URL);
    }

    public function showEditForm(): void
    {
        AuthGuard::requireRole(self::ROLE_ADMIN);

        $supplier = $this->findOrFail((int) ($_GET['id'] ?? 0));

        $this->render('suppliers/edit', [
            'supplier' => $supplier,
            'errors' => [],
            'old' => ['name' => $supplier->name, 'contact' => $supplier->contact, 'address' => $supplier->address],
        ]);
    }

    public function update(): void
    {
        AuthGuard::requireRole(self::ROLE_ADMIN);

        $id = (int) ($_POST['id'] ?? 0);
        $supplier = $this->findOrFail($id);

        $name = trim($_POST['name'] ?? '');
        $contact = trim($_POST['contact'] ?? '') ?: null;
        $address = trim($_POST['address'] ?? '') ?: null;

        $errors = $this->supplierService->validate($name);

        if (!empty($errors)) {
            $this->render('suppliers/edit', [
                'supplier' => $supplier,
                'errors' => $errors,
                'old' => ['name' => $name, 'contact' => $contact, 'address' => $address],
            ]);
            return;
        }

        $this->supplierService->updateSupplier($id, $name, $contact, $address);
        $this->redirect(self::INDEX_URL);
    }

    public function toggleActive(): void
    {
        AuthGuard::requireRole(self::ROLE_ADMIN);

        $id = (int) ($_POST['id'] ?? 0);
        $active = (int) ($_POST['active'] ?? 0) === 1;

        $this->supplierService->setActive($id, $active);

        $this->redirect(self::INDEX_URL);
    }

    private function findOrFail(int $id): Supplier
    {
        $supplier = $this->supplierService->findById($id);

        if ($supplier === null) {
            $this->abort(404, '404 Not Found — supplier not found.');
        }

        return $supplier;
    }
}
