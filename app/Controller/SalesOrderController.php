<?php

namespace App\Controller;

use App\Entity\SalesOrder;
use App\Service\CustomerService;
use App\Service\ProductService;
use App\Service\SalesOrderService;
use App\Service\WarehouseService;
use App\Support\AuthGuard;
use App\Support\CsvResponse;
use App\Support\InsufficientStockException;

final class SalesOrderController extends BaseController
{
    private const ROLES_VIEW = ['Admin', 'Sales', 'WarehouseStaff'];
    private const ROLES_EDIT = ['Admin', 'Sales'];
    private const ROLES_ISSUE = ['Admin', 'WarehouseStaff'];
    private const SHOW_URL = '/sales-orders/show?id=';

    public function __construct(
        private readonly SalesOrderService $salesOrderService,
        private readonly CustomerService $customerService,
        private readonly WarehouseService $warehouseService,
        private readonly ProductService $productService,
    ) {
    }

    public function index(): void
    {
        $user = AuthGuard::requireRole(self::ROLES_VIEW);

        $q = trim($_GET['q'] ?? '');
        $status = $_GET['status'] ?? '';
        $sort = ($_GET['sort'] ?? '') === 'date_asc' ? 'date_asc' : 'date_desc';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $createdBy = $user['role'] === 'Sales' ? (int) $user['id'] : null;

        $result = $this->salesOrderService->searchSalesOrders(
            $q !== '' ? $q : null,
            $status !== '' ? $status : null,
            $sort,
            $page,
            $createdBy,
        );

        $this->render('sales-orders/index', [
            'user' => $user,
            'q' => $q,
            'status' => $status,
            'sort' => $sort,
            'page' => $page,
            'createdBy' => $createdBy,
            'result' => $result,
        ]);
    }

    public function exportCsv(): void
    {
        $user = AuthGuard::requireRole(self::ROLES_VIEW);

        $q = trim($_GET['q'] ?? '');
        $status = $_GET['status'] ?? '';
        $sort = ($_GET['sort'] ?? '') === 'date_asc' ? 'date_asc' : 'date_desc';
        $createdBy = $user['role'] === 'Sales' ? (int) $user['id'] : null;

        $rows = $this->salesOrderService->exportSalesOrders($q !== '' ? $q : null, $status !== '' ? $status : null, $sort, $createdBy);

        CsvResponse::stream('sales-orders_' . date('Y-m-d') . '.csv', function ($out) use ($rows) {
            fputcsv($out, ['SO #', 'Customer', 'Warehouse', 'Status', 'Order Date']);
            foreach ($rows as $so) {
                fputcsv($out, [$so->id, $so->customerName, $so->warehouseName, $so->status, $so->orderDate]);
            }
        });
    }

    public function show(): void
    {
        $user = AuthGuard::requireRole(self::ROLES_VIEW);

        $salesOrder = $this->findOrFail((int) ($_GET['id'] ?? 0));
        $this->guardOwnershipForSales($user, $salesOrder);

        $this->renderShow($user, $salesOrder);
    }

    public function showCreateForm(): void
    {
        AuthGuard::requireRole(self::ROLES_EDIT);

        $this->renderCreateForm([], ['customer_id' => '', 'warehouse_id' => '', 'order_date' => date('Y-m-d')]);
    }

    public function create(): void
    {
        $user = AuthGuard::requireRole(self::ROLES_EDIT);

        $customerId = (int) ($_POST['customer_id'] ?? 0);
        $warehouseId = (int) ($_POST['warehouse_id'] ?? 0);
        $orderDate = trim($_POST['order_date'] ?? '');

        $errors = $this->salesOrderService->validateHeader($customerId, $warehouseId, $orderDate);

        if (!empty($errors)) {
            $this->renderCreateForm($errors, [
                'customer_id' => (string) $customerId,
                'warehouse_id' => (string) $warehouseId,
                'order_date' => $orderDate,
            ]);
            return;
        }

        $so = $this->salesOrderService->createSalesOrder($customerId, $warehouseId, $orderDate, (int) $user['id']);

        $this->redirectToShow($so->id);
    }

    public function addItem(): void
    {
        $user = AuthGuard::requireRole(self::ROLES_EDIT);

        $soId = (int) ($_POST['sales_order_id'] ?? 0);
        $salesOrder = $this->findOrFail($soId);
        $this->guardOwnershipForSales($user, $salesOrder);

        $productId = (int) ($_POST['product_id'] ?? 0);
        $qty = trim($_POST['qty'] ?? '');

        $addItemErrors = $this->salesOrderService->validateItem($salesOrder, $productId, $qty);

        if (!empty($addItemErrors)) {
            $this->renderShow($user, $salesOrder, [
                'addItemErrors' => $addItemErrors,
                'oldItem' => ['product_id' => (string) $productId, 'qty' => $qty],
            ]);
            return;
        }

        $product = $this->productService->findById($productId);
        $this->salesOrderService->addItem($soId, $productId, (int) $qty, $product->sellPrice);

        $this->redirectToShow($soId);
    }

    public function submit(): void
    {
        $user = AuthGuard::requireRole(self::ROLES_EDIT);

        $id = (int) ($_POST['id'] ?? 0);
        $salesOrder = $this->findOrFail($id);
        $this->guardOwnershipForSales($user, $salesOrder);

        $success = $this->salesOrderService->submitForApproval($id);

        $this->redirectToShow($id, $success ? null : 'cannot-submit');
    }

    public function approve(): void
    {
        $user = AuthGuard::requireRole(self::ROLE_ADMIN);

        $id = (int) ($_POST['id'] ?? 0);
        $success = $this->salesOrderService->approve($id, (int) $user['id']);

        $this->redirectToShow($id, $success ? null : 'cannot-approve');
    }

    public function reject(): void
    {
        AuthGuard::requireRole(self::ROLE_ADMIN);

        $id = (int) ($_POST['id'] ?? 0);
        $this->salesOrderService->reject($id);

        $this->redirectToShow($id);
    }

    public function cancel(): void
    {
        $user = AuthGuard::requireRole(self::ROLES_EDIT);

        $id = (int) ($_POST['id'] ?? 0);
        $salesOrder = $this->findOrFail($id);
        $this->guardOwnershipForSales($user, $salesOrder);

        $success = $this->salesOrderService->cancel($id);

        $this->redirectToShow($id, $success ? null : 'cannot-cancel');
    }

    public function issue(): void
    {
        $user = AuthGuard::requireRole(self::ROLES_ISSUE);

        $id = (int) ($_POST['id'] ?? 0);
        $salesOrder = $this->findOrFail($id);

        $issueErrors = $this->salesOrderService->validateGoodsIssue($salesOrder);

        if (empty($issueErrors)) {
            try {
                $this->salesOrderService->processGoodsIssue($salesOrder, (int) $user['id']);
            } catch (InsufficientStockException $e) {
                $issueErrors[] = $e->getMessage();
            }
        }

        if (!empty($issueErrors)) {
            // Ambil ulang dari DB supaya data yang tampil sesuai kondisi terbaru
            $this->renderShow($user, $this->findOrFail($id), ['issueErrors' => $issueErrors]);
            return;
        }

        $this->redirectToShow($id);
    }

    /**
     * Satu-satunya tempat yang me-render halaman detail SO.
     * $overrides menimpa nilai default (misalnya error dari form tertentu).
     */
    private function renderShow(array $user, SalesOrder $salesOrder, array $overrides = []): void
    {
        $this->render('sales-orders/show', array_merge([
            'user' => $user,
            'salesOrder' => $salesOrder,
            'products' => $this->productService->listProducts(),
            'addItemErrors' => [],
            'issueErrors' => [],
            'oldItem' => ['product_id' => '', 'qty' => ''],
        ], $overrides));
    }

    private function renderCreateForm(array $errors, array $old): void
    {
        $this->render('sales-orders/create', [
            'customers' => $this->customerService->listCustomers(),
            'warehouses' => $this->warehouseService->listWarehouses(),
            'errors' => $errors,
            'old' => $old,
        ]);
    }

    private function redirectToShow(int $id, ?string $error = null): never
    {
        $this->redirect(self::SHOW_URL . $id . ($error !== null ? '&error=' . $error : ''));
    }

    private function findOrFail(int $id): SalesOrder
    {
        $salesOrder = $this->salesOrderService->findById($id);

        if ($salesOrder === null) {
            $this->abort(404, '404 Not Found — sales order not found.');
        }

        return $salesOrder;
    }

    private function guardOwnershipForSales(array $user, SalesOrder $salesOrder): void
    {
        if ($user['role'] === 'Sales' && $salesOrder->createdBy !== (int) $user['id']) {
            $this->abort(403, '403 Forbidden — you do not have access to this sales order.');
        }
    }
}
