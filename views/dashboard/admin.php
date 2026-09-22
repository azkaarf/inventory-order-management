<?php
/** @var float $totalInventoryValue */
/** @var array $lowStockProducts */
/** @var array $poStatusCounts */
/** @var array $soStatusCounts */
use App\Support\StatusBadge;

$pageTitle = 'Dashboard';
require __DIR__ . '/../layout/header.php';
$poStatuses = ['Draft', 'Ordered', 'PartiallyReceived', 'Received', 'Cancelled'];
$soStatuses = ['Draft', 'PendingApproval', 'Approved', 'Fulfilled', 'Cancelled'];
?>
<h1>Dashboard</h1>

<div class="stat-grid">
    <div class="stat-card">
        <span class="stat-icon">💰</span>
        <div class="stat-value"><?= number_format($totalInventoryValue, 0, '.', ',') ?></div>
        <div class="stat-label">Inventory Value</div>
    </div>
    <div class="stat-card stat-warning">
        <span class="stat-icon">⚠️</span>
        <div class="stat-value"><?= count($lowStockProducts) ?></div>
        <div class="stat-label">Products Below Reorder Point</div>
    </div>
</div>

<div class="section-heading">
    <h2>Purchase Orders by Status</h2>
    <a href="/purchase-orders">View all &rarr;</a>
</div>
<div class="stat-grid">
    <?php foreach ($poStatuses as $s): ?>
        <div class="stat-card">
            <div class="stat-value"><?= $poStatusCounts[$s] ?? 0 ?></div>
            <div class="stat-label"><?= StatusBadge::render($s) ?></div>
        </div>
    <?php endforeach; ?>
</div>

<div class="section-heading">
    <h2>Sales Orders by Status</h2>
    <a href="/sales-orders">View all &rarr;</a>
</div>
<div class="stat-grid">
    <?php foreach ($soStatuses as $s): ?>
        <div class="stat-card">
            <div class="stat-value"><?= $soStatusCounts[$s] ?? 0 ?></div>
            <div class="stat-label"><?= StatusBadge::render($s) ?></div>
        </div>
    <?php endforeach; ?>
</div>

<h2>Products Below Reorder Point (<?= count($lowStockProducts) ?>)</h2>
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

<p><a href="/reports">Download reports (CSV) &rarr;</a></p>

<?php require __DIR__ . '/../layout/footer.php'; ?>
