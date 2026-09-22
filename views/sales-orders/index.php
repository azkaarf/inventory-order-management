<?php
/** @var array $result */
$pageTitle = 'Sales Orders';
require __DIR__ . '/../layout/header.php';
$q = $_GET['q'] ?? '';
$status = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'date_desc';
$statuses = ['Draft', 'PendingApproval', 'Approved', 'Fulfilled', 'Cancelled'];
?>
<h1>Sales Orders</h1>
<?php if (in_array($_SESSION['user']['role'], ['Admin', 'Sales'], true)): ?>
    <p><a href="/sales-orders/create">+ Create Sales Order</a></p>
<?php endif; ?>

<form method="GET" action="/sales-orders" class="stacked">
    <label>Search (SO # or customer) <input type="text" name="q" value="<?= htmlspecialchars($q) ?>"></label>
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
    <a href="/sales-orders" class="hint">Reset</a>
</form>

<?php if (($_SESSION['user']['role'] ?? null) === 'Sales'): ?>
    <p class="hint">Showing only sales orders you created.</p>
<?php endif; ?>

<table>
    <thead>
        <tr><th>#</th><th>Customer</th><th>Warehouse</th><th>Order Date</th><th>Status</th><th>Created By</th><th></th></tr>
    </thead>
    <tbody>
    <?php if (empty($result['items'])): ?>
        <tr><td colspan="7">No sales orders match your search/filter.</td></tr>
    <?php endif; ?>
    <?php foreach ($result['items'] as $so): ?>
        <tr>
            <td><?= $so->id ?></td>
            <td><?= htmlspecialchars($so->customerName) ?></td>
            <td><?= htmlspecialchars($so->warehouseName) ?></td>
            <td><?= htmlspecialchars($so->orderDate) ?></td>
            <td><?= \App\Support\StatusBadge::render($so->status) ?></td>
            <td><?= htmlspecialchars($so->createdByName) ?></td>
            <td><a href="/sales-orders/show?id=<?= $so->id ?>" title="View">👁️</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php
$page = $result['page'];
$totalPages = $result['totalPages'];
$baseUrl = '/sales-orders';
$queryParams = array_filter(['q' => $q, 'status' => $status, 'sort' => $sort]);
require __DIR__ . '/../partials/pagination.php';
?>

<?php require __DIR__ . '/../layout/footer.php'; ?>
