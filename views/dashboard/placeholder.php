<?php
/** @var array $user */
$pageTitle = 'Dashboard';
require __DIR__ . '/../layout/header.php';
?>
<h1>Dashboard</h1>
<p>Halo, <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['role']) ?>)</p>
<p class="hint">Placeholder — akan diganti dashboard sungguhan per role di Fase 6 (DASH-01).</p>

<?php require __DIR__ . '/../layout/footer.php'; ?>
