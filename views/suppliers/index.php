<?php
/** @var \App\Entity\Supplier[] $suppliers */
$pageTitle = 'Suppliers';
require __DIR__ . '/../layout/header.php';
?>
<p><a href="/suppliers/create">+ Add Supplier</a></p>

<table>
    <thead><tr><th>Name</th><th>Contact</th><th>Address</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php if (empty($suppliers)): ?>
        <tr><td colspan="5">No suppliers yet.</td></tr>
    <?php endif; ?>
    <?php foreach ($suppliers as $supplier): ?>
        <tr>
            <td><?= htmlspecialchars($supplier->name) ?></td>
            <td><?= htmlspecialchars($supplier->contact ?? '-') ?></td>
            <td><?= htmlspecialchars($supplier->address ?? '-') ?></td>
            <td><?= $supplier->isActive ? 'Active' : 'Inactive' ?></td>
            <td>
                <div class="icon-btn-group">
                    <a href="/suppliers/edit?id=<?= $supplier->id ?>" class="icon-btn" title="Edit">✏️</a>
                    <form method="POST" action="/suppliers/toggle">
                    <?= \App\Support\Csrf::field() ?>
                        <input type="hidden" name="id" value="<?= $supplier->id ?>">
                        <input type="hidden" name="active" value="<?= $supplier->isActive ? '0' : '1' ?>">
                        <button type="submit" class="icon-btn" title="<?= $supplier->isActive ? 'Deactivate' : 'Activate' ?>"><?= $supplier->isActive ? '🚫' : '✅' ?></button>
                    </form>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php require __DIR__ . '/../layout/footer.php'; ?>
