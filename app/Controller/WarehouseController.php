<?php

namespace App\Controller;

use App\Entity\Warehouse;
use App\Service\WarehouseService;
use App\Support\AuthGuard;

final class WarehouseController
{
    public function __construct(
        private readonly WarehouseService $warehouseService,
    ) {
    }

    public function index(): void
    {
        AuthGuard::requireRole(['Admin']);

        $warehouses = $this->warehouseService->listWarehouses();
        require __DIR__ . '/../../views/warehouses/index.php';
    }

    public function showCreateForm(): void
    {
        AuthGuard::requireRole(['Admin']);

        $errors = [];
        $old = ['name' => '', 'location' => ''];
        require __DIR__ . '/../../views/warehouses/create.php';
    }

    public function create(): void
    {
        AuthGuard::requireRole(['Admin']);

        $name = trim($_POST['name'] ?? '');
        $location = trim($_POST['location'] ?? '');

        $errors = $this->warehouseService->validate($name, $location);

        if (!empty($errors)) {
            $old = ['name' => $name, 'location' => $location];
            require __DIR__ . '/../../views/warehouses/create.php';
            return;
        }

        $this->warehouseService->createWarehouse($name, $location);
        header('Location: /warehouses');
        exit;
    }

    public function showEditForm(): void
    {
        AuthGuard::requireRole(['Admin']);

        $warehouse = $this->findOrFail((int) ($_GET['id'] ?? 0));
        $errors = [];
        $old = ['name' => $warehouse->name, 'location' => $warehouse->location];
        require __DIR__ . '/../../views/warehouses/edit.php';
    }

    public function update(): void
    {
        AuthGuard::requireRole(['Admin']);

        $id = (int) ($_POST['id'] ?? 0);
        $this->findOrFail($id);

        $name = trim($_POST['name'] ?? '');
        $location = trim($_POST['location'] ?? '');

        $errors = $this->warehouseService->validate($name, $location);

        if (!empty($errors)) {
            $old = ['name' => $name, 'location' => $location];
            require __DIR__ . '/../../views/warehouses/edit.php';
            return;
        }

        $this->warehouseService->updateWarehouse($id, $name, $location);
        header('Location: /warehouses');
        exit;
    }

    public function toggleActive(): void
    {
        AuthGuard::requireRole(['Admin']);

        $id = (int) ($_POST['id'] ?? 0);
        $active = (int) ($_POST['active'] ?? 0) === 1;

        $this->warehouseService->setActive($id, $active);

        header('Location: /warehouses');
        exit;
    }

    private function findOrFail(int $id): Warehouse
    {
        $warehouse = $this->warehouseService->findById($id);

        if ($warehouse === null) {
            http_response_code(404);
            echo '404 Not Found — warehouse not found.';
            exit;
        }

        return $warehouse;
    }
}
