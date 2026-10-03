<?php
/** @var array $result */
$pageTitle = 'Sales Orders';
$pageSubtitle = (($_SESSION['user']['role'] ?? null) === 'Sales')
    ? 'Sales orders you created, from draft through fulfillment.'
    : 'Track sales orders from draft through goods issue.';
require __DIR__ . '/../layout/header.php';
$q = $_GET['q'] ?? '';
$status = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'date_desc';
$statuses = ['Draft', 'PendingApproval', 'Approved', 'Fulfilled', 'Cancelled'];
$exportParams = array_filter(['q' => $q, 'status' => $status, 'sort' => $sort]);
?>

<form method="GET" action="/sales-orders" class="stacked table-toolbar">
    <label class="field">Search (SO # or customer) <input type="text" name="q" value="<?= htmlspecialchars($q) ?>"></label>
    <label class="field">Status
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
    <span class="table-toolbar-spacer"></span>
    <a class="btn-export" href="/sales-orders/export?<?= http_build_query($exportParams) ?>">⬇ Export CSV</a>
    <?php if (in_array($_SESSION['user']['role'], ['Admin', 'Sales'], true)): ?>
        <a href="/sales-orders/create" class="btn-primary">+ Create Sales Order</a>
    <?php endif; ?>
</form>

<table>
    <thead>
        <tr><th>#</th><th>Customer</th><th>Warehouse</th><th>Order Date</th><th>Status</th><th>Created By</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php if (empty($result['items'])): ?>
        <tr><td colspan="7">No sales orders match your search/filter.</td></tr>
    <?php endif; ?>
    <?php foreach ($result['items'] as $so): ?>
        <tr>
            <td>#<?= $so->id ?></td>
            <td><?= htmlspecialchars($so->customerName) ?></td>
            <td><?= htmlspecialchars($so->warehouseName) ?></td>
            <td><?= htmlspecialchars($so->orderDate) ?></td>
            <td><?= \App\Support\StatusBadge::render($so->status) ?></td>
            <td><?= htmlspecialchars($so->createdByName) ?></td>
            <td>
                <div class="icon-btn-group">
                    <a href="/sales-orders/show?id=<?= $so->id ?>" class="icon-btn" title="View">👁️</a>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php
$page = $result['page'];
$totalPages = $result['totalPages'];
$total = $result['total'];
$perPage = $result['perPage'];
$baseUrl = '/sales-orders';
$queryParams = $exportParams;
require __DIR__ . '/../partials/pagination.php';
?>

<?php require __DIR__ . '/../layout/footer.php'; ?>
