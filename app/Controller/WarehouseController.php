<?php

namespace App\Controller;

use App\Entity\Warehouse;
use App\Service\WarehouseService;
use App\Support\AuthGuard;

final class WarehouseController extends BaseController
{
    private const INDEX_URL = '/warehouses';

    public function __construct(
        private readonly WarehouseService $warehouseService,
    ) {
    }

    public function index(): void
    {
        AuthGuard::requireRole(self::ROLE_ADMIN);

        $this->render('warehouses/index', [
            'warehouses' => $this->warehouseService->listWarehouses(),
        ]);
    }

    public function showCreateForm(): void
    {
        AuthGuard::requireRole(self::ROLE_ADMIN);

        $this->render('warehouses/create', [
            'errors' => [],
            'old' => ['name' => '', 'location' => ''],
        ]);
    }

    public function create(): void
    {
        AuthGuard::requireRole(self::ROLE_ADMIN);

        $name = trim($_POST['name'] ?? '');
        $location = trim($_POST['location'] ?? '');

        $errors = $this->warehouseService->validate($name, $location);

        if (!empty($errors)) {
            $this->render('warehouses/create', [
                'errors' => $errors,
                'old' => ['name' => $name, 'location' => $location],
            ]);
            return;
        }

        $this->warehouseService->createWarehouse($name, $location);
        $this->redirect(self::INDEX_URL);
    }

    public function showEditForm(): void
    {
        AuthGuard::requireRole(self::ROLE_ADMIN);

        $warehouse = $this->findOrFail((int) ($_GET['id'] ?? 0));

        $this->render('warehouses/edit', [
            'warehouse' => $warehouse,
            'errors' => [],
            'old' => ['name' => $warehouse->name, 'location' => $warehouse->location],
        ]);
    }

    public function update(): void
    {
        AuthGuard::requireRole(self::ROLE_ADMIN);

        $id = (int) ($_POST['id'] ?? 0);
        $warehouse = $this->findOrFail($id);

        $name = trim($_POST['name'] ?? '');
        $location = trim($_POST['location'] ?? '');

        $errors = $this->warehouseService->validate($name, $location);

        if (!empty($errors)) {
            $this->render('warehouses/edit', [
                'warehouse' => $warehouse,
                'errors' => $errors,
                'old' => ['name' => $name, 'location' => $location],
            ]);
            return;
        }

        $this->warehouseService->updateWarehouse($id, $name, $location);
        $this->redirect(self::INDEX_URL);
    }

    public function toggleActive(): void
    {
        AuthGuard::requireRole(self::ROLE_ADMIN);

        $id = (int) ($_POST['id'] ?? 0);
        $active = (int) ($_POST['active'] ?? 0) === 1;

        $this->warehouseService->setActive($id, $active);

        $this->redirect(self::INDEX_URL);
    }

    private function findOrFail(int $id): Warehouse
    {
        $warehouse = $this->warehouseService->findById($id);

        if ($warehouse === null) {
            $this->abort(404, '404 Not Found — warehouse not found.');
        }

        return $warehouse;
    }
}
