<?php
/**
 * Shared layout header — di-require di awal tiap view.
 * Set $pageTitle sebelum require ini kalau mau judul tab custom.
 */
$pageTitle = $pageTitle ?? 'Inventory & Order Management System';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="topbar">
    <div>
        <span class="topbar-brand">IOMS</span>
        <?php if (isset($_SESSION['user'])): ?>
            <a href="/dashboard">Dashboard</a>
            <?php if ($_SESSION['user']['role'] === 'Admin'): ?>
                <a href="/users">Kelola User</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <div>
        <?php if (isset($_SESSION['user'])): ?>
            <span><?= htmlspecialchars($_SESSION['user']['name']) ?> (<?= htmlspecialchars($_SESSION['user']['role']) ?>)</span>
            <form method="POST" action="/logout">
                <button type="submit">Logout</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<div class="container">
