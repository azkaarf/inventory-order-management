<?php
/** @var array $result */
$pageTitle = 'Purchase Orders';
$pageSubtitle = 'Track purchase orders from draft through goods receipt.';
require __DIR__ . '/../layout/header.php';
$q = $_GET['q'] ?? '';
$status = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'date_desc';
$statuses = ['Draft', 'Ordered', 'PartiallyReceived', 'Received', 'Cancelled'];
$exportParams = array_filter(['q' => $q, 'status' => $status, 'sort' => $sort]);
?>

<form method="GET" action="/purchase-orders" class="stacked table-toolbar">
    <label class="field">Search (PO # or supplier) <input type="text" name="q" value="<?= htmlspecialchars($q) ?>"></label>
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
    <a href="/purchase-orders" class="hint">Reset</a>
    <span class="table-toolbar-spacer"></span>
    <a class="btn-export" href="/purchase-orders/export?<?= http_build_query($exportParams) ?>">⬇ Export CSV</a>
    <a href="/purchase-orders/create" class="btn-primary">+ Create Purchase Order</a>
</form>

<table>
    <thead>
        <tr><th>#</th><th>Supplier</th><th>Warehouse</th><th>Order Date</th><th>Status</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php if (empty($result['items'])): ?>
        <tr><td colspan="6">No purchase orders match your search/filter.</td></tr>
    <?php endif; ?>
    <?php foreach ($result['items'] as $po): ?>
        <tr>
            <td>#<?= $po->id ?></td>
            <td><?= htmlspecialchars($po->supplierName) ?></td>
            <td><?= htmlspecialchars($po->warehouseName) ?></td>
            <td><?= htmlspecialchars($po->orderDate) ?></td>
            <td><?= \App\Support\StatusBadge::render($po->status) ?></td>
            <td>
                <div class="icon-btn-group">
                    <a href="/purchase-orders/show?id=<?= $po->id ?>" class="icon-btn" title="View">👁️</a>
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
$baseUrl = '/purchase-orders';
$queryParams = $exportParams;
require __DIR__ . '/../partials/pagination.php';
?>

<?php require __DIR__ . '/../layout/footer.php'; ?>
