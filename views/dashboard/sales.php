<?php
/** @var array $soStatusCounts */

$pageTitle = 'Dashboard';
$pageSubtitle = 'A quick look at the status of your own Sales Orders.';
require __DIR__ . '/../layout/header.php';
$soStatuses = ['Draft', 'PendingApproval', 'Approved', 'Fulfilled', 'Cancelled'];
?>

<div class="section-heading">
    <h2>My Sales Orders by Status</h2>
</div>
<?php $statuses = $soStatuses; $counts = $soStatusCounts; require __DIR__ . '/../partials/status-progress.php'; ?>

<p><a href="/sales-orders">View my Sales Orders &rarr;</a></p>
<p><a href="/reports">Download my orders report (CSV) &rarr;</a></p>

<?php require __DIR__ . '/../layout/footer.php'; ?>
