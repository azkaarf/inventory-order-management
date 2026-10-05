<?php

namespace App\Controller;

use App\Entity\PurchaseOrder;
use App\Entity\PurchaseOrderItem;
use App\Service\ProductService;
use App\Service\PurchaseOrderService;
use App\Service\SupplierService;
use App\Service\WarehouseService;
use App\Support\AuthGuard;
use App\Support\CsvResponse;

final class PurchaseOrderController extends BaseController
{
    private const ALLOWED_ROLES = ['Admin', 'WarehouseStaff'];
    private const SHOW_URL = '/purchase-orders/show?id=';

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

        $this->render('purchase-orders/index', [
            'q' => $q,
            'status' => $status,
            'sort' => $sort,
            'page' => $page,
            'result' => $result,
        ]);
    }

    public function exportCsv(): void
    {
        AuthGuard::requireRole(self::ALLOWED_ROLES);

        $q = trim($_GET['q'] ?? '');
        $status = $_GET['status'] ?? '';
        $sort = ($_GET['sort'] ?? '') === 'date_asc' ? 'date_asc' : 'date_desc';

        $rows = $this->purchaseOrderService->exportPurchaseOrders($q !== '' ? $q : null, $status !== '' ? $status : null, $sort);

        CsvResponse::stream('purchase-orders_' . date('Y-m-d') . '.csv', function ($out) use ($rows) {
            fputcsv($out, ['PO #', 'Supplier', 'Warehouse', 'Status', 'Order Date']);
            foreach ($rows as $po) {
                fputcsv($out, [$po->id, $po->supplierName, $po->warehouseName, $po->status, $po->orderDate]);
            }
        });
    }

    public function show(): void
    {
        AuthGuard::requireRole(self::ALLOWED_ROLES);

        $purchaseOrder = $this->findOrFail((int) ($_GET['id'] ?? 0));

        $this->renderShow($purchaseOrder);
    }

    public function showCreateForm(): void
    {
        AuthGuard::requireRole(self::ALLOWED_ROLES);

        $this->renderCreateForm([], ['supplier_id' => '', 'warehouse_id' => '', 'order_date' => date('Y-m-d')]);
    }

    public function create(): void
    {
        $user = AuthGuard::requireRole(self::ALLOWED_ROLES);

        $supplierId = (int) ($_POST['supplier_id'] ?? 0);
        $warehouseId = (int) ($_POST['warehouse_id'] ?? 0);
        $orderDate = trim($_POST['order_date'] ?? '');

        $errors = $this->purchaseOrderService->validateHeader($supplierId, $warehouseId, $orderDate);

        if (!empty($errors)) {
            $this->renderCreateForm($errors, [
                'supplier_id' => (string) $supplierId,
                'warehouse_id' => (string) $warehouseId,
                'order_date' => $orderDate,
            ]);
            return;
        }

        $po = $this->purchaseOrderService->createPurchaseOrder($supplierId, $warehouseId, $orderDate, (int) $user['id']);

        $this->redirectToShow($po->id);
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
            $this->renderShow($purchaseOrder, [
                'addItemErrors' => $addItemErrors,
                'oldItem' => ['product_id' => (string) $productId, 'qty' => $qty, 'buy_price' => $buyPrice],
            ]);
            return;
        }

        $this->purchaseOrderService->addItem($poId, $productId, (int) $qty, (float) $buyPrice);

        $this->redirectToShow($poId);
    }

    public function markAsOrdered(): void
    {
        AuthGuard::requireRole(self::ALLOWED_ROLES);

        $id = (int) ($_POST['id'] ?? 0);
        $success = $this->purchaseOrderService->markAsOrdered($id);

        $this->redirectToShow($id, $success ? null : 'cannot-order');
    }

    public function cancel(): void
    {
        AuthGuard::requireRole(self::ALLOWED_ROLES);

        $id = (int) ($_POST['id'] ?? 0);
        $success = $this->purchaseOrderService->cancel($id);

        $this->redirectToShow($id, $success ? null : 'cannot-cancel');
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
            $this->renderShow($purchaseOrder, ['receiveErrors' => $receiveErrors]);
            return;
        }

        $this->purchaseOrderService->receiveItem($purchaseOrder, $item, $qty, (int) $user['id']);

        $this->redirectToShow($poId);
    }

    /**
     * Satu-satunya tempat yang me-render halaman detail PO.
     * $overrides menimpa nilai default (misalnya error dari form tertentu).
     */
    private function renderShow(PurchaseOrder $purchaseOrder, array $overrides = []): void
    {
        $this->render('purchase-orders/show', array_merge([
            'purchaseOrder' => $purchaseOrder,
            'products' => $this->productService->listProducts(),
            'receiveErrors' => [],
            'addItemErrors' => [],
            'oldItem' => ['product_id' => '', 'qty' => '', 'buy_price' => ''],
        ], $overrides));
    }

    private function renderCreateForm(array $errors, array $old): void
    {
        $this->render('purchase-orders/create', [
            'suppliers' => $this->supplierService->listSuppliers(),
            'warehouses' => $this->warehouseService->listWarehouses(),
            'errors' => $errors,
            'old' => $old,
        ]);
    }

    private function redirectToShow(int $id, ?string $error = null): never
    {
        $this->redirect(self::SHOW_URL . $id . ($error !== null ? '&error=' . $error : ''));
    }

    private function findOrFail(int $id): PurchaseOrder
    {
        $purchaseOrder = $this->purchaseOrderService->findById($id);

        if ($purchaseOrder === null) {
            $this->abort(404, '404 Not Found — purchase order not found.');
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

        $this->abort(404, '404 Not Found — purchase order item not found.');
    }
}
