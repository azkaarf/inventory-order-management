<?php
/** @var \App\Entity\Warehouse[] $warehouses */
$pageTitle = 'Warehouses';
require __DIR__ . '/../layout/header.php';
?>
<h1>Warehouses</h1>
<p><a href="/warehouses/create">+ Add Warehouse</a></p>

<table>
    <thead>
        <tr><th>Name</th><th>Location</th><th>Status</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php if (empty($warehouses)): ?>
        <tr><td colspan="4">No warehouses yet.</td></tr>
    <?php endif; ?>
    <?php foreach ($warehouses as $warehouse): ?>
        <tr>
            <td><?= htmlspecialchars($warehouse->name) ?></td>
            <td><?= htmlspecialchars($warehouse->location) ?></td>
            <td><?= $warehouse->isActive ? 'Active' : 'Inactive' ?></td>
            <td>
                <a href="/warehouses/edit?id=<?= $warehouse->id ?>" title="Edit">✏️</a>
                <form method="POST" action="/warehouses/toggle" style="display:inline">
                <?= \App\Support\Csrf::field() ?>
                    <input type="hidden" name="id" value="<?= $warehouse->id ?>">
                    <input type="hidden" name="active" value="<?= $warehouse->isActive ? '0' : '1' ?>">
                    <button type="submit" title="<?= $warehouse->isActive ? 'Deactivate' : 'Activate' ?>"><?= $warehouse->isActive ? '🚫' : '✅' ?></button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php require __DIR__ . '/../layout/footer.php'; ?>
