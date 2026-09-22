<?php

namespace App\Controller;

use App\Entity\Supplier;
use App\Service\SupplierService;
use App\Support\AuthGuard;

final class SupplierController
{
    public function __construct(
        private readonly SupplierService $supplierService,
    ) {
    }

    public function index(): void
    {
        AuthGuard::requireRole(['Admin']);

        $suppliers = $this->supplierService->listSuppliers();
        require __DIR__ . '/../../views/suppliers/index.php';
    }

    public function showCreateForm(): void
    {
        AuthGuard::requireRole(['Admin']);

        $errors = [];
        $old = ['name' => '', 'contact' => '', 'address' => ''];
        require __DIR__ . '/../../views/suppliers/create.php';
    }

    public function create(): void
    {
        AuthGuard::requireRole(['Admin']);

        $name = trim($_POST['name'] ?? '');
        $contact = trim($_POST['contact'] ?? '') ?: null;
        $address = trim($_POST['address'] ?? '') ?: null;

        $errors = $this->supplierService->validate($name);

        if (!empty($errors)) {
            $old = ['name' => $name, 'contact' => $contact, 'address' => $address];
            require __DIR__ . '/../../views/suppliers/create.php';
            return;
        }

        $this->supplierService->createSupplier($name, $contact, $address);
        header('Location: /suppliers');
        exit;
    }

    public function showEditForm(): void
    {
        AuthGuard::requireRole(['Admin']);

        $supplier = $this->findOrFail((int) ($_GET['id'] ?? 0));
        $errors = [];
        $old = ['name' => $supplier->name, 'contact' => $supplier->contact, 'address' => $supplier->address];
        require __DIR__ . '/../../views/suppliers/edit.php';
    }

    public function update(): void
    {
        AuthGuard::requireRole(['Admin']);

        $id = (int) ($_POST['id'] ?? 0);
        $this->findOrFail($id);

        $name = trim($_POST['name'] ?? '');
        $contact = trim($_POST['contact'] ?? '') ?: null;
        $address = trim($_POST['address'] ?? '') ?: null;

        $errors = $this->supplierService->validate($name);

        if (!empty($errors)) {
            $old = ['name' => $name, 'contact' => $contact, 'address' => $address];
            require __DIR__ . '/../../views/suppliers/edit.php';
            return;
        }

        $this->supplierService->updateSupplier($id, $name, $contact, $address);
        header('Location: /suppliers');
        exit;
    }

    public function toggleActive(): void
    {
        AuthGuard::requireRole(['Admin']);

        $id = (int) ($_POST['id'] ?? 0);
        $active = (int) ($_POST['active'] ?? 0) === 1;

        $this->supplierService->setActive($id, $active);

        header('Location: /suppliers');
        exit;
    }

    private function findOrFail(int $id): Supplier
    {
        $supplier = $this->supplierService->findById($id);

        if ($supplier === null) {
            http_response_code(404);
            echo '404 Not Found — supplier not found.';
            exit;
        }

        return $supplier;
    }
}
