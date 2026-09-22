<?php
/** @var array $poAwaitingReceipt */
/** @var array $soAwaitingIssue */
/** @var array $lowStockProducts */
use App\Support\StatusBadge;

$pageTitle = 'Dashboard';
require __DIR__ . '/../layout/header.php';
?>
<h1>Dashboard</h1>

<div class="stat-grid">
    <div class="stat-card">
        <span class="stat-icon">📥</span>
        <div class="stat-value"><?= count($poAwaitingReceipt) ?></div>
        <div class="stat-label">Purchase Orders Awaiting Receipt</div>
    </div>
    <div class="stat-card">
        <span class="stat-icon">📤</span>
        <div class="stat-value"><?= count($soAwaitingIssue) ?></div>
        <div class="stat-label">Sales Orders Awaiting Goods Issue</div>
    </div>
    <div class="stat-card stat-warning">
        <span class="stat-icon">⚠️</span>
        <div class="stat-value"><?= count($lowStockProducts) ?></div>
        <div class="stat-label">Products Below Reorder Point</div>
    </div>
</div>

<h2>Purchase Orders Awaiting Receipt</h2>
<table>
    <thead><tr><th>#</th><th>Supplier</th><th>Status</th><th>Order Date</th><th></th></tr></thead>
    <tbody>
    <?php if (empty($poAwaitingReceipt)): ?>
        <tr><td colspan="5">Nothing waiting on goods receipt.</td></tr>
    <?php endif; ?>
    <?php foreach ($poAwaitingReceipt as $po): ?>
        <tr>
            <td><?= $po['id'] ?></td>
            <td><?= htmlspecialchars($po['supplier_name']) ?></td>
            <td><?= StatusBadge::render($po['status']) ?></td>
            <td><?= htmlspecialchars($po['order_date']) ?></td>
            <td><a href="/purchase-orders/show?id=<?= $po['id'] ?>">Process &rarr;</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<h2>Sales Orders Awaiting Goods Issue</h2>
<table>
    <thead><tr><th>#</th><th>Customer</th><th>Status</th><th>Order Date</th><th></th></tr></thead>
    <tbody>
    <?php if (empty($soAwaitingIssue)): ?>
        <tr><td colspan="5">Nothing waiting on goods issue.</td></tr>
    <?php endif; ?>
    <?php foreach ($soAwaitingIssue as $so): ?>
        <tr>
            <td><?= $so['id'] ?></td>
            <td><?= htmlspecialchars($so['customer_name']) ?></td>
            <td><?= StatusBadge::render($so['status']) ?></td>
            <td><?= htmlspecialchars($so['order_date']) ?></td>
            <td><a href="/sales-orders/show?id=<?= $so['id'] ?>">Process &rarr;</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<h2>Low-Stock Products</h2>
<table>
    <thead><tr><th>SKU</th><th>Name</th><th>Stock</th><th>Reorder Point</th></tr></thead>
    <tbody>
    <?php if (empty($lowStockProducts)): ?>
        <tr><td colspan="4">No products are below their reorder point right now.</td></tr>
    <?php endif; ?>
    <?php foreach ($lowStockProducts as $product): ?>
        <tr>
            <td><?= htmlspecialchars($product['sku']) ?></td>
            <td><a href="/products/show?id=<?= $product['id'] ?>"><?= htmlspecialchars($product['name']) ?></a></td>
            <td><?= $product['total_stock'] ?></td>
            <td><?= $product['reorder_point'] ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<p><a href="/reports">Download stock report (CSV) &rarr;</a></p>

<?php require __DIR__ . '/../layout/footer.php'; ?>
