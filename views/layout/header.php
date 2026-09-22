<?php
/**
 * Shared layout header — required at the top of every view.
 * Set $pageTitle before requiring this if you want a custom tab title.
 */
$pageTitle = $pageTitle ?? 'BLISS — Beauty Logistics, Inventory & Sales System';
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$isActive = function (string $path) use ($currentPath): string {
    return str_starts_with($currentPath, $path) ? ' active' : '';
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= @filemtime(__DIR__ . '/../../public/assets/css/app.css') ?: time() ?>">
</head>
<body>
<div class="app-shell">
    <?php if (isset($_SESSION['user'])): ?>
    <aside class="sidebar">
        <div class="sidebar-brand">
            BLISS
            <div class="sidebar-tagline">Beauty Logistics, Inventory &amp; Sales System</div>
        </div>
        <nav class="sidebar-nav">
            <a class="nav-link<?= $isActive('/dashboard') ?>" href="/dashboard">Dashboard</a>
            <a class="nav-link<?= $isActive('/products') ?>" href="/products">Products</a>
            <a class="nav-link<?= $isActive('/reports') ?>" href="/reports">Reports</a>

            <div class="sidebar-section-title">Orders</div>
            <a class="nav-link<?= $isActive('/sales-orders') ?>" href="/sales-orders">Sales Orders</a>
            <?php if (in_array($_SESSION['user']['role'], ['Admin', 'WarehouseStaff'], true)): ?>
                <a class="nav-link<?= $isActive('/purchase-orders') ?>" href="/purchase-orders">Purchase Orders</a>
            <?php endif; ?>

            <?php if ($_SESSION['user']['role'] === 'Admin'): ?>
                <div class="sidebar-section-title">Master Data</div>
                <a class="nav-link<?= $isActive('/categories') ?>" href="/categories">Categories</a>
                <a class="nav-link<?= $isActive('/warehouses') ?>" href="/warehouses">Warehouses</a>
                <a class="nav-link<?= $isActive('/suppliers') ?>" href="/suppliers">Suppliers</a>
                <a class="nav-link<?= $isActive('/customers') ?>" href="/customers">Customers</a>
                <a class="nav-link<?= $isActive('/users') ?>" href="/users">Manage Users</a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-footer">
            <span class="user-name"><?= htmlspecialchars($_SESSION['user']['name']) ?> (<?= htmlspecialchars($_SESSION['user']['role']) ?>)</span>
            <form method="POST" action="/logout">
                <?= \App\Support\Csrf::field() ?>
                <button type="submit">Logout</button>
            </form>
        </div>
    </aside>
    <?php endif; ?>
    <div class="main">
        <div class="container">
