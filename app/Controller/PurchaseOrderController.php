<?php

namespace App\Controller;

use App\Entity\PurchaseOrder;
use App\Entity\PurchaseOrderItem;
use App\Service\ProductService;
use App\Service\PurchaseOrderService;
use App\Service\SupplierService;
use App\Service\WarehouseService;
use App\Support\AuthGuard;

final class PurchaseOrderController
{
    private const ALLOWED_ROLES = ['Admin', 'WarehouseStaff'];

    public function __construct(
        private readonly PurchaseOrderService $purchaseOrderService,
        private readonly SupplierService $supplierService,
        private readonly WarehouseService $warehouseService,
        private readonly ProductService $productService,
    ) {
    }

    public function index(): void
    {
        AuthGuard::requireRole(self::ALLOWED_ROLES);

        $q = trim($_GET['q'] ?? '');
        $status = $_GET['status'] ?? '';
        $sort = ($_GET['sort'] ?? '') === 'date_asc' ? 'date_asc' : 'date_desc';
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $result = $this->purchaseOrderService->searchPurchaseOrders(
            $q !== '' ? $q : null,
            $status !== '' ? $status : null,
            $sort,
            $page,
        );

        require __DIR__ . '/../../views/purchase-orders/index.php';
    }

    public function show(): void
    {
        AuthGuard::requireRole(self::ALLOWED_ROLES);

        $purchaseOrder = $this->findOrFail((int) ($_GET['id'] ?? 0));
        $products = $this->productService->listProducts();
        $receiveErrors = [];
        $addItemErrors = [];
        $oldItem = ['product_id' => '', 'qty' => '', 'buy_price' => ''];

        require __DIR__ . '/../../views/purchase-orders/show.php';
    }

    public function showCreateForm(): void
    {
        AuthGuard::requireRole(self::ALLOWED_ROLES);

        $suppliers = $this->supplierService->listSuppliers();
        $warehouses = $this->warehouseService->listWarehouses();
        $errors = [];
        $old = ['supplier_id' => '', 'warehouse_id' => '', 'order_date' => date('Y-m-d')];

        require __DIR__ . '/../../views/purchase-orders/create.php';
    }

    public function create(): void
    {
        $user = AuthGuard::requireRole(self::ALLOWED_ROLES);

        $supplierId = (int) ($_POST['supplier_id'] ?? 0);
        $warehouseId = (int) ($_POST['warehouse_id'] ?? 0);
        $orderDate = trim($_POST['order_date'] ?? '');

        $errors = $this->purchaseOrderService->validateHeader($supplierId, $warehouseId, $orderDate);

        if (!empty($errors)) {
            $suppliers = $this->supplierService->listSuppliers();
            $warehouses = $this->warehouseService->listWarehouses();
            $old = ['supplier_id' => (string) $supplierId, 'warehouse_id' => (string) $warehouseId, 'order_date' => $orderDate];
            require __DIR__ . '/../../views/purchase-orders/create.php';
            return;
        }

        $po = $this->purchaseOrderService->createPurchaseOrder($supplierId, $warehouseId, $orderDate, (int) $user['id']);

        header('Location: /purchase-orders/show?id=' . $po->id);
        exit;
    }

    public function addItem(): void
    {
        AuthGuard::requireRole(self::ALLOWED_ROLES);

        $poId = (int) ($_POST['purchase_order_id'] ?? 0);
        $purchaseOrder = $this->findOrFail($poId);

        $productId = (int) ($_POST['product_id'] ?? 0);
        $qty = trim($_POST['qty'] ?? '');
        $buyPrice = trim($_POST['buy_price'] ?? '');

        $addItemErrors = $this->purchaseOrderService->validateItem($purchaseOrder, $productId, $qty, $buyPrice);

        if (!empty($addItemErrors)) {
            $products = $this->productService->listProducts();
            $receiveErrors = [];
            $oldItem = ['product_id' => (string) $productId, 'qty' => $qty, 'buy_price' => $buyPrice];
            require __DIR__ . '/../../views/purchase-orders/show.php';
            return;
        }

        $this->purchaseOrderService->addItem($poId, $productId, (int) $qty, (float) $buyPrice);

        header('Location: /purchase-orders/show?id=' . $poId);
        exit;
    }

    public function markAsOrdered(): void
    {
        AuthGuard::requireRole(self::ALLOWED_ROLES);

        $id = (int) ($_POST['id'] ?? 0);
        $success = $this->purchaseOrderService->markAsOrdered($id);

        header('Location: /purchase-orders/show?id=' . $id . ($success ? '' : '&error=cannot-order'));
        exit;
    }

    public function cancel(): void
    {
        AuthGuard::requireRole(self::ALLOWED_ROLES);

        $id = (int) ($_POST['id'] ?? 0);
        $success = $this->purchaseOrderService->cancel($id);

        header('Location: /purchase-orders/show?id=' . $id . ($success ? '' : '&error=cannot-cancel'));
        exit;
    }

    public function receiveItem(): void
    {
        $user = AuthGuard::requireRole(self::ALLOWED_ROLES);

        $poId = (int) ($_POST['purchase_order_id'] ?? 0);
        $itemId = (int) ($_POST['item_id'] ?? 0);
        $qty = (int) ($_POST['qty'] ?? 0);

        $purchaseOrder = $this->findOrFail($poId);
        $item = $this->findItemOrFail($purchaseOrder, $itemId);

        $receiveErrors = $this->purchaseOrderService->validateReceipt($purchaseOrder, $item, $qty);

        if (!empty($receiveErrors)) {
            $products = $this->productService->listProducts();
            $addItemErrors = [];
            require __DIR__ . '/../../views/purchase-orders/show.php';
            return;
        }

        $this->purchaseOrderService->receiveItem($purchaseOrder, $item, $qty, (int) $user['id']);

        header('Location: /purchase-orders/show?id=' . $poId);
        exit;
    }

    private function findOrFail(int $id): PurchaseOrder
    {
        $purchaseOrder = $this->purchaseOrderService->findById($id);

        if ($purchaseOrder === null) {
            http_response_code(404);
            echo '404 Not Found — purchase order not found.';
            exit;
        }

        return $purchaseOrder;
    }

    private function findItemOrFail(PurchaseOrder $po, int $itemId): PurchaseOrderItem
    {
        foreach ($po->items as $item) {
            if ($item->id === $itemId) {
                return $item;
            }
        }

        http_response_code(404);
        echo '404 Not Found — purchase order item not found.';
        exit;
    }
}
