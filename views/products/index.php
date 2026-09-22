<?php
/** @var array $result */
/** @var \App\Entity\Category[] $categories */
$pageTitle = 'Products';
require __DIR__ . '/../layout/header.php';
$isAdmin = ($_SESSION['user']['role'] ?? null) === 'Admin';
$q = $_GET['q'] ?? '';
$categoryId = $_GET['category_id'] ?? '';
$stockStatus = $_GET['stock_status'] ?? '';
?>
<h1>Products</h1>
<?php if ($isAdmin): ?>
    <p><a href="/products/create">+ Add Product</a></p>
<?php endif; ?>

<form method="GET" action="/products" class="stacked">
    <label>Search (name or SKU) <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="e.g. Product 01 or SKU-0001"></label>
    <label>Category
        <select name="category_id">
            <option value="">All categories</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= $category->id ?>" <?= (string) $category->id === (string) $categoryId ? 'selected' : '' ?>>
                    <?= htmlspecialchars($category->name) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Stock status
        <select name="stock_status">
            <option value="">All</option>
            <option value="low" <?= $stockStatus === 'low' ? 'selected' : '' ?>>Low stock</option>
            <option value="normal" <?= $stockStatus === 'normal' ? 'selected' : '' ?>>Normal</option>
        </select>
    </label>
    <button type="submit">Filter</button>
    <a href="/products" class="hint">Reset</a>
</form>

<table>
    <thead>
        <tr>
            <th>SKU</th><th>Name</th><th>Category</th><th>Unit</th>
            <th>Sell Price</th><th>Total Stock</th><th>Reorder Point</th><th>Status</th><th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($result['items'])): ?>
        <tr><td colspan="9">No products match your search/filter.</td></tr>
    <?php endif; ?>
    <?php foreach ($result['items'] as $product): ?>
        <?php $isLow = (int) $product['total_stock'] < (int) $product['reorder_point']; ?>
        <tr>
            <td><?= htmlspecialchars($product['sku']) ?></td>
            <td><a href="/products/show?id=<?= $product['id'] ?>"><?= htmlspecialchars($product['name']) ?></a></td>
            <td><?= htmlspecialchars($product['category_name']) ?></td>
            <td><?= htmlspecialchars($product['unit']) ?></td>
            <td><?= number_format((float) $product['sell_price'], 0, '.', ',') ?></td>
            <td><?= $product['total_stock'] ?><?= $isLow ? ' ⚠️ low' : '' ?></td>
            <td><?= $product['reorder_point'] ?></td>
            <td><?= \App\Support\StatusBadge::render($product['is_active'] ? 'Active' : 'Inactive') ?></td>
            <td>
                <?php if ($isAdmin): ?>
                    <a href="/products/edit?id=<?= $product['id'] ?>" title="Edit">✏️</a>
                    <form method="POST" action="/products/toggle" style="display:inline">
                <?= \App\Support\Csrf::field() ?>
                        <input type="hidden" name="id" value="<?= $product['id'] ?>">
                        <input type="hidden" name="active" value="<?= $product['is_active'] ? '0' : '1' ?>">
                        <button type="submit" title="<?= $product['is_active'] ? 'Deactivate' : 'Activate' ?>"><?= $product['is_active'] ? '🚫' : '✅' ?></button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php
$page = $result['page'];
$totalPages = $result['totalPages'];
$baseUrl = '/products';
$queryParams = array_filter(['q' => $q, 'category_id' => $categoryId, 'stock_status' => $stockStatus]);
require __DIR__ . '/../partials/pagination.php';
?>

<?php require __DIR__ . '/../layout/footer.php'; ?>
