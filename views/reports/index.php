<?php
/** @var string $from */
/** @var string $to */
/** @var string|null $error */
$pageTitle = 'Reports';
require __DIR__ . '/../layout/header.php';
$role = $_SESSION['user']['role'];
?>
<h1>Reports</h1>

<?php if ($error === 'invalid-range'): ?>
    <div class="alert-error">That date range is invalid — check both dates and make sure "From" isn't after "To".</div>
<?php endif; ?>

<form method="GET" action="/reports" class="stacked">
    <label>From <input type="date" name="from" value="<?= htmlspecialchars($from) ?>"></label>
    <label>To <input type="date" name="to" value="<?= htmlspecialchars($to) ?>"></label>
    <button type="submit">Update range</button>
</form>

<p>
    <?php if (in_array($role, ['Admin', 'WarehouseStaff'], true)): ?>
        <a href="/reports/stock-ledger.csv?from=<?= htmlspecialchars($from) ?>&to=<?= htmlspecialchars($to) ?>">Download Stock Ledger CSV</a><br>
    <?php endif; ?>
    <?php if (in_array($role, ['Admin', 'Sales'], true)): ?>
        <a href="/reports/orders.csv?from=<?= htmlspecialchars($from) ?>&to=<?= htmlspecialchars($to) ?>">
            Download Orders CSV<?= $role === 'Sales' ? ' (my orders only)' : '' ?>
        </a>
    <?php endif; ?>
</p>

<?php require __DIR__ . '/../layout/footer.php'; ?>
