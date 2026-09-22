<?php
/** @var array $soStatusCounts */
use App\Support\StatusBadge;

$pageTitle = 'Dashboard';
require __DIR__ . '/../layout/header.php';
$soStatuses = ['Draft', 'PendingApproval', 'Approved', 'Fulfilled', 'Cancelled'];
?>
<h1>Dashboard</h1>
<h2>My Sales Orders by Status</h2>

<div class="stat-grid">
    <?php foreach ($soStatuses as $s): ?>
        <div class="stat-card">
            <div class="stat-value"><?= $soStatusCounts[$s] ?? 0 ?></div>
            <div class="stat-label"><?= StatusBadge::render($s) ?></div>
        </div>
    <?php endforeach; ?>
</div>

<p><a href="/sales-orders">View my Sales Orders &rarr;</a></p>
<p><a href="/reports">Download my orders report (CSV) &rarr;</a></p>

<?php require __DIR__ . '/../layout/footer.php'; ?>
