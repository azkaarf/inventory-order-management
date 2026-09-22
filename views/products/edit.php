<?php
/** @var string[] $errors */
/** @var array $old */
/** @var \App\Entity\Category[] $categories */
/** @var int $productId */
$pageTitle = 'Edit Product';
require __DIR__ . '/../layout/header.php';
?>
<h1>Edit Product</h1>
<p><a href="/products">&larr; Back</a></p>

<?php if (!empty($errors)): ?>
    <div class="alert-error">
        <ul><?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="POST" action="/products/edit" enctype="multipart/form-data" class="stacked">
                <?= \App\Support\Csrf::field() ?>
    <input type="hidden" name="id" value="<?= $productId ?>">
    <label>SKU (cannot be changed) <input type="text" value="<?= htmlspecialchars($old['sku']) ?>" disabled></label>
    <label>Name <input type="text" name="name" value="<?= htmlspecialchars($old['name']) ?>" required></label>
    <label>Category
        <select name="category_id" required>
            <?php foreach ($categories as $category): ?>
                <option value="<?= $category->id ?>" <?= (string) $category->id === $old['category_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($category->name) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Unit <input type="text" name="unit" value="<?= htmlspecialchars($old['unit']) ?>" required></label>
    <label>Buy Price <input type="number" step="0.01" min="0" name="buy_price" value="<?= htmlspecialchars($old['buy_price']) ?>" required></label>
    <label>Sell Price <input type="number" step="0.01" min="0" name="sell_price" value="<?= htmlspecialchars($old['sell_price']) ?>" required></label>
    <label>Reorder Point <input type="number" step="1" min="0" name="reorder_point" value="<?= htmlspecialchars($old['reorder_point']) ?>" required></label>
    <label>Replace Image (optional) <input type="file" name="image" accept="image/jpeg,image/png,image/webp"></label>
    <button type="submit">Save</button>
</form>

<?php require __DIR__ . '/../layout/footer.php'; ?>
