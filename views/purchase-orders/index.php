<?php
/** @var array $result */
$pageTitle = 'Purchase Orders';
require __DIR__ . '/../layout/header.php';
$q = $_GET['q'] ?? '';
$status = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'date_desc';
$statuses = ['Draft', 'Ordered', 'PartiallyReceived', 'Received', 'Cancelled'];
?>
<h1>Purchase Orders</h1>
<p><a href="/purchase-orders/create">+ Create Purchase Order</a></p>

<form method="GET" action="/purchase-orders" class="stacked">
    <label>Search (PO # or supplier) <input type="text" name="q" value="<?= htmlspecialchars($q) ?>"></label>
    <label>Status
        <select name="status">
            <option value="">All statuses</option>
            <?php foreach ($statuses as $s): ?>
                <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <div class="field">
        <span class="field-label">Sort by order date</span>
        <label class="radio-inline">
            <input type="radio" name="sort" value="date_desc" <?= $sort === 'date_desc' ? 'checked' : '' ?>>
            Newest first
        </label>
        <label class="radio-inline">
            <input type="radio" name="sort" value="date_asc" <?= $sort === 'date_asc' ? 'checked' : '' ?>>
            Oldest first
        </label>
    </div>
    <button type="submit">Filter</button>
    <a href="/purchase-orders" class="hint">Reset</a>
</form>

<table>
    <thead>
        <tr><th>#</th><th>Supplier</th><th>Warehouse</th><th>Order Date</th><th>Status</th><th></th></tr>
    </thead>
    <tbody>
    <?php if (empty($result['items'])): ?>
        <tr><td colspan="6">No purchase orders match your search/filter.</td></tr>
    <?php endif; ?>
    <?php foreach ($result['items'] as $po): ?>
        <tr>
            <td><?= $po->id ?></td>
            <td><?= htmlspecialchars($po->supplierName) ?></td>
            <td><?= htmlspecialchars($po->warehouseName) ?></td>
            <td><?= htmlspecialchars($po->orderDate) ?></td>
            <td><?= \App\Support\StatusBadge::render($po->status) ?></td>
            <td><a href="/purchase-orders/show?id=<?= $po->id ?>" title="View">👁️</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php
$page = $result['page'];
$totalPages = $result['totalPages'];
$baseUrl = '/purchase-orders';
$queryParams = array_filter(['q' => $q, 'status' => $status, 'sort' => $sort]);
require __DIR__ . '/../partials/pagination.php';
?>

<?php require __DIR__ . '/../layout/footer.php'; ?>
