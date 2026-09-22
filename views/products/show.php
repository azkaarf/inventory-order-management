<?php
/** @var \App\Entity\Product $product */
/** @var array $stockBreakdown */
/** @var int $totalStock */
$pageTitle = $product->name;
require __DIR__ . '/../layout/header.php';
?>
<h1><?= htmlspecialchars($product->name) ?></h1>
<p><a href="/products">&larr; Back</a></p>

<?php if ($product->imagePath): ?>
    <p><img src="/<?= htmlspecialchars($product->imagePath) ?>" alt="Product image" style="max-width:240px;border:1px solid #e4e7eb;border-radius:4px;"></p>
<?php endif; ?>

<table>
    <tr><th>SKU</th><td><?= htmlspecialchars($product->sku) ?></td></tr>
    <tr><th>Unit</th><td><?= htmlspecialchars($product->unit) ?></td></tr>
    <tr><th>Buy Price</th><td><?= number_format($product->buyPrice, 0, '.', ',') ?></td></tr>
    <tr><th>Sell Price</th><td><?= number_format($product->sellPrice, 0, '.', ',') ?></td></tr>
    <tr><th>Reorder Point</th><td><?= $product->reorderPoint ?></td></tr>
    <tr><th>Status</th><td><?= $product->isActive ? 'Active' : 'Inactive' ?></td></tr>
</table>

<h2>Stock per Warehouse</h2>
<table>
    <thead><tr><th>Warehouse</th><th>Quantity</th></tr></thead>
    <tbody>
    <?php if (empty($stockBreakdown)): ?>
        <tr><td colspan="2">No stock data yet.</td></tr>
    <?php endif; ?>
    <?php foreach ($stockBreakdown as $row): ?>
        <tr>
            <td><?= htmlspecialchars($row['warehouse_name']) ?></td>
            <td><?= $row['quantity'] ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr><th>Total</th><th><?= $totalStock ?></th></tr>
    </tfoot>
</table>

<?php require __DIR__ . '/../layout/footer.php'; ?>
