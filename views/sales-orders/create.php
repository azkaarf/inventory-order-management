<?php
/** @var string[] $errors */
/** @var array $old */
/** @var \App\Entity\Customer[] $customers */
/** @var \App\Entity\Warehouse[] $warehouses */
$pageTitle = 'Create Sales Order';
require __DIR__ . '/../layout/header.php';
?>
<h1>Create Sales Order</h1>
<p><a href="/sales-orders">&larr; Back</a></p>

<?php if (!empty($errors)): ?>
    <div class="alert-error">
        <ul><?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="POST" action="/sales-orders/create" class="stacked">
                <?= \App\Support\Csrf::field() ?>
    <label>Customer
        <select name="customer_id" required>
            <option value="">-- select --</option>
            <?php foreach ($customers as $customer): ?>
                <option value="<?= $customer->id ?>" <?= (string) $customer->id === $old['customer_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($customer->name) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Source Warehouse
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
<p class="hint">You'll add items on the next page — stock availability is checked live as you pick each product.</p>

<?php require __DIR__ . '/../layout/footer.php'; ?>
