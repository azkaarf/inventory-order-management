<?php
$pageTitle = $pageTitle ?? 'BLISS — Beauty Logistics, Inventory & Sales System';
$pageSubtitle = $pageSubtitle ?? null;
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$isActive = function (string $path) use ($currentPath): string {
    return str_starts_with($currentPath, $path) ? ' active' : '';
};
$userInitials = static function (string $name): string {
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $letters = array_map(
        static fn (string $p): string => mb_strtoupper(mb_substr($p, 0, 1)),
        array_slice($parts, 0, 2),
    );
    return implode('', $letters) !== '' ? implode('', $letters) : '?';
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
    </aside>
    <?php endif; ?>
    <div class="main">
        <?php if (isset($_SESSION['user'])): ?>
        <header class="topbar">
            <div class="topbar-search">
                <input type="text" placeholder="Search is available on each list page &rarr;" disabled>
            </div>
            <div class="topbar-user">
                <div class="topbar-user-avatar"><?= htmlspecialchars($userInitials($_SESSION['user']['name'])) ?></div>
                <div class="topbar-user-info">
                    <span class="topbar-user-name"><?= htmlspecialchars($_SESSION['user']['name']) ?></span>
                    <span class="topbar-user-role"><?= htmlspecialchars($_SESSION['user']['role']) ?></span>
                </div>
                <form method="POST" action="/logout">
                    <?= \App\Support\Csrf::field() ?>
                    <button type="submit" class="topbar-logout-btn">Logout</button>
                </form>
            </div>
        </header>
        <?php endif; ?>
        <div class="container">
            <?php if (isset($_SESSION['user'])): ?>
            <div class="page-header">
                <div class="breadcrumb">
                    <a href="/dashboard">Dashboard</a>
                    <?php if ($pageTitle !== 'Dashboard'): ?>
                        <span class="sep">/</span>
                        <span><?= htmlspecialchars($pageTitle) ?></span>
                    <?php endif; ?>
                </div>
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
                <?php if ($pageSubtitle): ?>
                    <p class="page-subtitle"><?= htmlspecialchars($pageSubtitle) ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
