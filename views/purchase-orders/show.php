<?php
/** @var \App\Entity\PurchaseOrder $purchaseOrder */
/** @var \App\Entity\Product[] $products */
/** @var string[] $receiveErrors */
/** @var string[] $addItemErrors */
/** @var array $oldItem */
$pageTitle = 'Purchase Order #' . $purchaseOrder->id;
require __DIR__ . '/../layout/header.php';
$error = $_GET['error'] ?? null;
?>
<h1>Purchase Order #<?= $purchaseOrder->id ?></h1>
<p><a href="/purchase-orders">&larr; Back</a></p>

<?php if ($error === 'cannot-order'): ?>
    <div class="alert-error">Cannot mark as Ordered — add at least one item first.</div>
<?php elseif ($error === 'cannot-cancel'): ?>
    <div class="alert-error">This purchase order can no longer be cancelled.</div>
<?php endif; ?>

<table>
    <tr><th>Supplier</th><td><?= htmlspecialchars($purchaseOrder->supplierName) ?></td></tr>
    <tr><th>Warehouse</th><td><?= htmlspecialchars($purchaseOrder->warehouseName) ?></td></tr>
    <tr><th>Order Date</th><td><?= htmlspecialchars($purchaseOrder->orderDate) ?></td></tr>
    <tr><th>Status</th><td><?= \App\Support\StatusBadge::render($purchaseOrder->status) ?></td></tr>
</table>

<p>
    <?php if ($purchaseOrder->status === 'Draft'): ?>
        <form method="POST" action="/purchase-orders/order" style="display:inline">
                <?= \App\Support\Csrf::field() ?>
            <input type="hidden" name="id" value="<?= $purchaseOrder->id ?>">
            <button type="submit" title="Mark as Ordered">📦</button>
        </form>
    <?php endif; ?>
    <?php if (!in_array($purchaseOrder->status, ['Received', 'Cancelled'], true)): ?>
        <form method="POST" action="/purchase-orders/cancel" style="display:inline" onsubmit="return confirm('Cancel this purchase order?');">
                <?= \App\Support\Csrf::field() ?>
            <input type="hidden" name="id" value="<?= $purchaseOrder->id ?>">
            <button type="submit" title="Cancel">✖️</button>
        </form>
    <?php endif; ?>
</p>

<h2>Items</h2>

<?php if (!empty($receiveErrors)): ?>
    <div class="alert-error">
        <ul><?php foreach ($receiveErrors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<table>
    <thead>
        <tr><th>Product</th><th>Qty Ordered</th><th>Qty Received</th><th>Remaining</th><th>Buy Price</th><th></th></tr>
    </thead>
    <tbody>
    <?php if (empty($purchaseOrder->items)): ?>
        <tr><td colspan="6">No items yet.</td></tr>
    <?php endif; ?>
    <?php foreach ($purchaseOrder->items as $item): ?>
        <tr>
            <td><?= htmlspecialchars($item->productName) ?></td>
            <td><?= $item->qtyOrdered ?></td>
            <td><?= $item->qtyReceived ?></td>
            <td><?= $item->qtyRemaining() ?></td>
            <td><?= number_format($item->buyPrice, 0, '.', ',') ?></td>
            <td>
                <?php if (in_array($purchaseOrder->status, ['Ordered', 'PartiallyReceived'], true) && $item->qtyRemaining() > 0): ?>
                    <form method="POST" action="/purchase-orders/receive-item" style="display:inline">
                <?= \App\Support\Csrf::field() ?>
                        <input type="hidden" name="purchase_order_id" value="<?= $purchaseOrder->id ?>">
                        <input type="hidden" name="item_id" value="<?= $item->id ?>">
                        <input type="number" name="qty" min="1" max="<?= $item->qtyRemaining() ?>" style="width:80px" required>
                        <button type="submit" title="Receive">📥</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php if ($purchaseOrder->status === 'Draft'): ?>
    <h2>Add Item</h2>

    <?php if (!empty($addItemErrors)): ?>
        <div class="alert-error">
            <ul><?php foreach ($addItemErrors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="/purchase-orders/add-item" class="stacked">
                <?= \App\Support\Csrf::field() ?>
        <input type="hidden" name="purchase_order_id" value="<?= $purchaseOrder->id ?>">
        <label>Product
            <select name="product_id" required>
                <option value="">-- select --</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?= $product->id ?>" <?= (string) $product->id === $oldItem['product_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($product->sku . ' — ' . $product->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Quantity <input type="number" name="qty" min="1" value="<?= htmlspecialchars($oldItem['qty']) ?>" required></label>
        <label>Buy Price <input type="number" step="0.01" min="0" name="buy_price" value="<?= htmlspecialchars($oldItem['buy_price']) ?>" required></label>
        <button type="submit" title="Add Item">➕</button>
    </form>
<?php endif; ?>

<?php require __DIR__ . '/../layout/footer.php'; ?>
