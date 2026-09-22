<?php
/** @var string[] $errors */
/** @var array $old */
/** @var \App\Entity\Supplier[] $suppliers */
/** @var \App\Entity\Warehouse[] $warehouses */
$pageTitle = 'Create Purchase Order';
require __DIR__ . '/../layout/header.php';
?>
<h1>Create Purchase Order</h1>
<p><a href="/purchase-orders">&larr; Back</a></p>

<?php if (!empty($errors)): ?>
    <div class="alert-error">
        <ul><?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="POST" action="/purchase-orders/create" class="stacked">
                <?= \App\Support\Csrf::field() ?>
    <label>Supplier
        <select name="supplier_id" required>
            <option value="">-- select --</option>
            <?php foreach ($suppliers as $supplier): ?>
                <option value="<?= $supplier->id ?>" <?= (string) $supplier->id === $old['supplier_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($supplier->name) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Destination Warehouse
        <select name="warehouse_id" required>
            <option value="">-- select --</option>
            <?php foreach ($warehouses as $warehouse): ?>
                <option value="<?= $warehouse->id ?>" <?= (string) $warehouse->id === $old['warehouse_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($warehouse->name) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Order Date <input type="date" name="order_date" value="<?= htmlspecialchars($old['order_date']) ?>" required></label>
    <button type="submit">Create (Draft)</button>
</form>
<p class="hint">You'll add items on the next page.</p>

<?php require __DIR__ . '/../layout/footer.php'; ?>
