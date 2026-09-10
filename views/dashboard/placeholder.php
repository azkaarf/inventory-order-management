<?php /** @var array $user */ ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
</head>
<body>
    <h1>Dashboard</h1>
    <p>Halo, <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['role']) ?>)</p>
    <p><em>Placeholder — akan diganti dashboard sungguhan per role di Fase 6 (DASH-01).</em></p>

    <?php if ($user['role'] === 'Admin'): ?>
        <p><a href="/users">Kelola User</a></p>
    <?php endif; ?>

    <form method="POST" action="/logout">
        <button type="submit">Logout</button>
    </form>
</body>
</html>
