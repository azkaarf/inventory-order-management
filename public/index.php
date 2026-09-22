<?php

declare(strict_types=1);

require __DIR__ . '/../config/bootstrap.php';

use App\Controller\ApiController;
use App\Controller\AuthController;
use App\Controller\CategoryController;
use App\Controller\CustomerController;
use App\Controller\DashboardController;
use App\Controller\ProductController;
use App\Controller\PurchaseOrderController;
use App\Controller\ReportController;
use App\Controller\SalesOrderController;
use App\Controller\SupplierController;
use App\Controller\UserController;
use App\Controller\WarehouseController;
use App\Support\Csrf;
use App\Repository\MySqlCategoryRepository;
use App\Repository\MySqlCustomerRepository;
use App\Repository\MySqlDashboardRepository;
use App\Repository\MySqlGoodsIssueRepository;
use App\Repository\MySqlGoodsReceiptRepository;
use App\Repository\MySqlProductRepository;
use App\Repository\MySqlPurchaseOrderRepository;
use App\Repository\MySqlSalesOrderRepository;
use App\Repository\MySqlStockRepository;
use App\Repository\MySqlSupplierRepository;
use App\Repository\MySqlUserRepository;
use App\Repository\MySqlWarehouseRepository;
use App\Service\AuthService;
use App\Service\CategoryService;
use App\Service\CustomerService;
use App\Service\DashboardService;
use App\Service\ProductService;
use App\Service\PurchaseOrderService;
use App\Service\SalesOrderService;
use App\Service\SupplierService;
use App\Service\UserService;
use App\Service\WarehouseService;

$userRepository = new MySqlUserRepository();
$categoryRepository = new MySqlCategoryRepository();
$warehouseRepository = new MySqlWarehouseRepository();
$productRepository = new MySqlProductRepository();
$supplierRepository = new MySqlSupplierRepository();
$customerRepository = new MySqlCustomerRepository();
$stockRepository = new MySqlStockRepository();

$productService = new ProductService($productRepository, $categoryRepository, $stockRepository);
$supplierService = new SupplierService($supplierRepository);
$customerService = new CustomerService($customerRepository);
$warehouseService = new WarehouseService($warehouseRepository);

// API-01: one dynamic route, handled as a special case (a full regex router
// would be overkill for a single endpoint at this stage — see tech-debt notes).
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET' && preg_match('#^/api/products/([^/]+)/availability$#', $path, $matches)) {
    (new ApiController($productRepository, $stockRepository))->productAvailability($matches[1]);
    exit;
}

$authController = new AuthController(new AuthService($userRepository));
$userController = new UserController(new UserService($userRepository));
$categoryController = new CategoryController(new CategoryService($categoryRepository));
$warehouseController = new WarehouseController($warehouseService);
$productController = new ProductController($productService, new CategoryService($categoryRepository));
$supplierController = new SupplierController($supplierService);
$customerController = new CustomerController($customerService);

$purchaseOrderService = new PurchaseOrderService(
    new MySqlPurchaseOrderRepository(),
    $productRepository,
    $supplierRepository,
    $warehouseRepository,
    new MySqlGoodsReceiptRepository(),
);
$purchaseOrderController = new PurchaseOrderController(
    $purchaseOrderService,
    $supplierService,
    $warehouseService,
    $productService,
);

$salesOrderService = new SalesOrderService(
    new MySqlSalesOrderRepository(),
    $productRepository,
    $customerRepository,
    $warehouseRepository,
    new MySqlGoodsIssueRepository(),
);
$salesOrderController = new SalesOrderController(
    $salesOrderService,
    $customerService,
    $warehouseService,
    $productService,
);

$dashboardService = new DashboardService(new MySqlDashboardRepository());
$dashboardController = new DashboardController($dashboardService);
$reportController = new ReportController($dashboardService);

$routes = [
    'GET /'              => [$authController, 'showLoginForm'],
    'GET /login'         => [$authController, 'showLoginForm'],
    'POST /login'        => [$authController, 'login'],
    'POST /logout'       => [$authController, 'logout'],
    'GET /dashboard'     => [$dashboardController, 'show'],

    'GET /reports'                  => [$reportController, 'index'],
    'GET /reports/stock-ledger.csv' => [$reportController, 'stockLedgerCsv'],
    'GET /reports/orders.csv'       => [$reportController, 'ordersCsv'],

    'GET /users'         => [$userController, 'index'],
    'GET /users/create'  => [$userController, 'showCreateForm'],
    'POST /users/create' => [$userController, 'create'],
    'GET /users/edit'    => [$userController, 'showEditForm'],
    'POST /users/edit'   => [$userController, 'update'],
    'POST /users/toggle' => [$userController, 'toggleActive'],

    'GET /categories'         => [$categoryController, 'index'],
    'GET /categories/create'  => [$categoryController, 'showCreateForm'],
    'POST /categories/create' => [$categoryController, 'create'],
    'GET /categories/edit'    => [$categoryController, 'showEditForm'],
    'POST /categories/edit'   => [$categoryController, 'update'],
    'POST /categories/delete' => [$categoryController, 'delete'],

    'GET /warehouses'         => [$warehouseController, 'index'],
    'GET /warehouses/create'  => [$warehouseController, 'showCreateForm'],
    'POST /warehouses/create' => [$warehouseController, 'create'],
    'GET /warehouses/edit'    => [$warehouseController, 'showEditForm'],
    'POST /warehouses/edit'   => [$warehouseController, 'update'],
    'POST /warehouses/toggle' => [$warehouseController, 'toggleActive'],

    'GET /products'         => [$productController, 'index'],
    'GET /products/show'    => [$productController, 'show'],
    'GET /products/create'  => [$productController, 'showCreateForm'],
    'POST /products/create' => [$productController, 'create'],
    'GET /products/edit'    => [$productController, 'showEditForm'],
    'POST /products/edit'   => [$productController, 'update'],
    'POST /products/toggle' => [$productController, 'toggleActive'],

    'GET /suppliers'         => [$supplierController, 'index'],
    'GET /suppliers/create'  => [$supplierController, 'showCreateForm'],
    'POST /suppliers/create' => [$supplierController, 'create'],
    'GET /suppliers/edit'    => [$supplierController, 'showEditForm'],
    'POST /suppliers/edit'   => [$supplierController, 'update'],
    'POST /suppliers/toggle' => [$supplierController, 'toggleActive'],

    'GET /customers'         => [$customerController, 'index'],
    'GET /customers/create'  => [$customerController, 'showCreateForm'],
    'POST /customers/create' => [$customerController, 'create'],
    'GET /customers/edit'    => [$customerController, 'showEditForm'],
    'POST /customers/edit'   => [$customerController, 'update'],
    'POST /customers/toggle' => [$customerController, 'toggleActive'],

    'GET /purchase-orders'               => [$purchaseOrderController, 'index'],
    'GET /purchase-orders/show'          => [$purchaseOrderController, 'show'],
    'GET /purchase-orders/create'        => [$purchaseOrderController, 'showCreateForm'],
    'POST /purchase-orders/create'       => [$purchaseOrderController, 'create'],
    'POST /purchase-orders/add-item'     => [$purchaseOrderController, 'addItem'],
    'POST /purchase-orders/order'        => [$purchaseOrderController, 'markAsOrdered'],
    'POST /purchase-orders/cancel'       => [$purchaseOrderController, 'cancel'],
    'POST /purchase-orders/receive-item' => [$purchaseOrderController, 'receiveItem'],

    'GET /sales-orders'          => [$salesOrderController, 'index'],
    'GET /sales-orders/show'     => [$salesOrderController, 'show'],
    'GET /sales-orders/create'   => [$salesOrderController, 'showCreateForm'],
    'POST /sales-orders/create'  => [$salesOrderController, 'create'],
    'POST /sales-orders/add-item' => [$salesOrderController, 'addItem'],
    'POST /sales-orders/submit'  => [$salesOrderController, 'submit'],
    'POST /sales-orders/approve' => [$salesOrderController, 'approve'],
    'POST /sales-orders/reject'  => [$salesOrderController, 'reject'],
    'POST /sales-orders/cancel'  => [$salesOrderController, 'cancel'],
    'POST /sales-orders/issue'   => [$salesOrderController, 'issue'],
];

$key = "$method $path";

if (!isset($routes[$key])) {
    http_response_code(404);
    echo '404 Not Found';
    exit;
}

// Bab 6.2: CSRF check terpusat di satu tempat (bukan diulang per Controller),
// sama seperti AuthGuard - berlaku buat semua route POST tanpa kecuali.
if ($method === 'POST' && !Csrf::verify($_POST['_csrf'] ?? null)) {
    http_response_code(403);
    echo '403 Forbidden — invalid or missing CSRF token. Please go back and try again.';
    exit;
}

[$controller, $action] = $routes[$key];
$controller->$action();
