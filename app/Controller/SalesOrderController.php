<?php

namespace App\Controller;

use App\Entity\SalesOrder;
use App\Service\CustomerService;
use App\Service\ProductService;
use App\Service\SalesOrderService;
use App\Service\WarehouseService;
use App\Support\AuthGuard;
use App\Support\InsufficientStockException;

final class SalesOrderController
{
    public function __construct(
        private readonly SalesOrderService $salesOrderService,
        private readonly CustomerService $customerService,
        private readonly WarehouseService $warehouseService,
        private readonly ProductService $productService,
    ) {
    }

    public function index(): void
    {
        $user = AuthGuard::requireRole(['Admin', 'Sales', 'WarehouseStaff']);

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

        require __DIR__ . '/../../views/sales-orders/index.php';
    }

    public function show(): void
    {
        $user = AuthGuard::requireRole(['Admin', 'Sales', 'WarehouseStaff']);

        $salesOrder = $this->findOrFail((int) ($_GET['id'] ?? 0));
        $this->guardOwnershipForSales($user, $salesOrder);

        $products = $this->productService->listProducts();
        $addItemErrors = [];
        $issueErrors = [];
        $oldItem = ['product_id' => '', 'qty' => ''];

        require __DIR__ . '/../../views/sales-orders/show.php';
    }

    public function showCreateForm(): void
    {
        AuthGuard::requireRole(['Admin', 'Sales']);

        $customers = $this->customerService->listCustomers();
        $warehouses = $this->warehouseService->listWarehouses();
        $errors = [];
        $old = ['customer_id' => '', 'warehouse_id' => '', 'order_date' => date('Y-m-d')];

        require __DIR__ . '/../../views/sales-orders/create.php';
    }

    public function create(): void
    {
        $user = AuthGuard::requireRole(['Admin', 'Sales']);

        $customerId = (int) ($_POST['customer_id'] ?? 0);
        $warehouseId = (int) ($_POST['warehouse_id'] ?? 0);
        $orderDate = trim($_POST['order_date'] ?? '');

        $errors = $this->salesOrderService->validateHeader($customerId, $warehouseId, $orderDate);

        if (!empty($errors)) {
            $customers = $this->customerService->listCustomers();
            $warehouses = $this->warehouseService->listWarehouses();
            $old = ['customer_id' => (string) $customerId, 'warehouse_id' => (string) $warehouseId, 'order_date' => $orderDate];
            require __DIR__ . '/../../views/sales-orders/create.php';
            return;
        }

        $so = $this->salesOrderService->createSalesOrder($customerId, $warehouseId, $orderDate, (int) $user['id']);

        header('Location: /sales-orders/show?id=' . $so->id);
        exit;
    }

    public function addItem(): void
    {
        $user = AuthGuard::requireRole(['Admin', 'Sales']);

        $soId = (int) ($_POST['sales_order_id'] ?? 0);
        $salesOrder = $this->findOrFail($soId);
        $this->guardOwnershipForSales($user, $salesOrder);

        $productId = (int) ($_POST['product_id'] ?? 0);
        $qty = trim($_POST['qty'] ?? '');

        $addItemErrors = $this->salesOrderService->validateItem($salesOrder, $productId, $qty);

        if (!empty($addItemErrors)) {
            $products = $this->productService->listProducts();
            $issueErrors = [];
            $oldItem = ['product_id' => (string) $productId, 'qty' => $qty];
            require __DIR__ . '/../../views/sales-orders/show.php';
            return;
        }

        $product = $this->productService->findById($productId);
        $this->salesOrderService->addItem($soId, $productId, (int) $qty, $product->sellPrice);

        header('Location: /sales-orders/show?id=' . $soId);
        exit;
    }

    public function submit(): void
    {
        $user = AuthGuard::requireRole(['Admin', 'Sales']);

        $id = (int) ($_POST['id'] ?? 0);
        $salesOrder = $this->findOrFail($id);
        $this->guardOwnershipForSales($user, $salesOrder);

        $success = $this->salesOrderService->submitForApproval($id);

        header('Location: /sales-orders/show?id=' . $id . ($success ? '' : '&error=cannot-submit'));
        exit;
    }

    public function approve(): void
    {
        $user = AuthGuard::requireRole(['Admin']);

        $id = (int) ($_POST['id'] ?? 0);
        $success = $this->salesOrderService->approve($id, (int) $user['id']);

        header('Location: /sales-orders/show?id=' . $id . ($success ? '' : '&error=cannot-approve'));
        exit;
    }

    public function reject(): void
    {
        AuthGuard::requireRole(['Admin']);

        $id = (int) ($_POST['id'] ?? 0);
        $this->salesOrderService->reject($id);

        header('Location: /sales-orders/show?id=' . $id);
        exit;
    }

    public function cancel(): void
    {
        $user = AuthGuard::requireRole(['Admin', 'Sales']);

        $id = (int) ($_POST['id'] ?? 0);
        $salesOrder = $this->findOrFail($id);
        $this->guardOwnershipForSales($user, $salesOrder);

        $success = $this->salesOrderService->cancel($id);

        header('Location: /sales-orders/show?id=' . $id . ($success ? '' : '&error=cannot-cancel'));
        exit;
    }

    public function issue(): void
    {
        $user = AuthGuard::requireRole(['Admin', 'WarehouseStaff']);

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
            $salesOrder = $this->salesOrderService->findById($id);
            $products = $this->productService->listProducts();
            $addItemErrors = [];
            require __DIR__ . '/../../views/sales-orders/show.php';
            return;
        }

        header('Location: /sales-orders/show?id=' . $id);
        exit;
    }

    private function findOrFail(int $id): SalesOrder
    {
        $salesOrder = $this->salesOrderService->findById($id);

        if ($salesOrder === null) {
            http_response_code(404);
            echo '404 Not Found — sales order not found.';
            exit;
        }

        return $salesOrder;
    }

    private function guardOwnershipForSales(array $user, SalesOrder $salesOrder): void
    {
        if ($user['role'] === 'Sales' && $salesOrder->createdBy !== (int) $user['id']) {
            http_response_code(403);
            echo '403 Forbidden — you do not have access to this sales order.';
            exit;
        }
    }
}
