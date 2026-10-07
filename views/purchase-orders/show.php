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

<div class="action-bar">
    <?php if ($purchaseOrder->status === 'Draft'): ?>
        <form method="POST" action="/purchase-orders/order">
            <?= \App\Support\Csrf::field() ?>
            <input type="hidden" name="id" value="<?= $purchaseOrder->id ?>">
            <button type="submit" class="btn-action" title="Mark as sent to the supplier (items can no longer be added)">
                <span class="btn-icon">📦</span> Mark as Ordered
            </button>
        </form>
    <?php endif; ?>
    <?php if (!in_array($purchaseOrder->status, ['Received', 'Cancelled'], true)): ?>
        <form method="POST" action="/purchase-orders/cancel" onsubmit="return confirm('Cancel this purchase order?');">
            <?= \App\Support\Csrf::field() ?>
            <input type="hidden" name="id" value="<?= $purchaseOrder->id ?>">
            <button type="submit" class="btn-action btn-cancel" title="Cancel this purchase order">
                <span class="btn-icon">✖️</span> Cancel Order
            </button>
        </form>
    <?php endif; ?>
</div>

<h2>Items</h2>

<?php if (!empty($receiveErrors)): ?>
    <div class="alert-error">
        <ul><?php foreach ($receiveErrors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<table>
    <thead>
        <tr><th>Product</th><th>Qty Ordered</th><th>Qty Received</th><th>Remaining</th><th>Buy Price</th><th>Receive</th></tr>
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
                    <form method="POST" action="/purchase-orders/receive-item" class="receive-inline">
                <?= \App\Support\Csrf::field() ?>
                        <input type="hidden" name="purchase_order_id" value="<?= $purchaseOrder->id ?>">
                        <input type="hidden" name="item_id" value="<?= $item->id ?>">
                        <input type="number" name="qty" min="1" max="<?= $item->qtyRemaining() ?>" style="width:80px" placeholder="Qty" required>
                        <button type="submit" class="btn-action btn-sm" title="Receive this quantity into the warehouse">
                            <span class="btn-icon">📥</span> Receive
                        </button>
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
            <select name="product_id" id="po-product-select" required>
                <option value="">-- select --</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?= $product->id ?>"
                            data-buy-price="<?= number_format($product->buyPrice, 2, '.', '') ?>"
                            <?= (string) $product->id === $oldItem['product_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($product->sku . ' — ' . $product->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Quantity <input type="number" name="qty" min="1" value="<?= htmlspecialchars($oldItem['qty']) ?>" required></label>
        <label>Buy Price
            <input type="number" step="0.01" min="0" name="buy_price" id="po-buy-price" value="<?= htmlspecialchars($oldItem['buy_price']) ?>" required>
            <small class="hint" id="po-buy-price-hint"></small>
        </label>
        <button type="submit" class="btn-action" title="Add Item"><span class="btn-icon">➕</span> Add Item</button>
    </form>

    <script>
    (() => {
        'use strict';

        const productSelect = document.getElementById('po-product-select');
        const priceInput = document.getElementById('po-buy-price');
        const priceHint = document.getElementById('po-buy-price-hint');
        if (!productSelect || !priceInput || !priceHint) {
            return;
        }

        const referencePrice = () => {
            const selected = productSelect.options[productSelect.selectedIndex];
            const raw = selected ? selected.getAttribute('data-buy-price') : null;
            return raw === null || raw === '' ? null : raw;
        };

        const showHint = () => {
            const price = referencePrice();
            priceHint.textContent = price === null
                ? ''
                : `Reference price from Products: ${Number(price).toLocaleString('en-US', { minimumFractionDigits: 2 })}. Change it if this order's price is different.`;
        };

        productSelect.addEventListener('change', () => {
            const price = referencePrice();
            if (price !== null) {
                priceInput.value = price;
            }
            showHint();
        });

        showHint();
    })();
    </script>
<?php endif; ?>

<?php require __DIR__ . '/../layout/footer.php'; ?>