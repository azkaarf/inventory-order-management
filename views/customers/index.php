<?php
/** @var \App\Entity\Customer[] $customers */
$pageTitle = 'Customers';
require __DIR__ . '/../layout/header.php';
?>
<p><a href="/customers/create">+ Add Customer</a></p>

<table>
    <thead><tr><th>Name</th><th>Contact</th><th>Address</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php if (empty($customers)): ?>
        <tr><td colspan="5">No customers yet.</td></tr>
    <?php endif; ?>
    <?php foreach ($customers as $customer): ?>
        <tr>
            <td><?= htmlspecialchars($customer->name) ?></td>
            <td><?= htmlspecialchars($customer->contact ?? '-') ?></td>
            <td><?= htmlspecialchars($customer->address ?? '-') ?></td>
            <td><?= $customer->isActive ? 'Active' : 'Inactive' ?></td>
            <td>
                <div class="icon-btn-group">
                    <a href="/customers/edit?id=<?= $customer->id ?>" class="icon-btn" title="Edit">✏️</a>
                    <form method="POST" action="/customers/toggle">
                    <?= \App\Support\Csrf::field() ?>
                        <input type="hidden" name="id" value="<?= $customer->id ?>">
                        <input type="hidden" name="active" value="<?= $customer->isActive ? '0' : '1' ?>">
                        <button type="submit" class="icon-btn" title="<?= $customer->isActive ? 'Deactivate' : 'Activate' ?>"><?= $customer->isActive ? '🚫' : '✅' ?></button>
                    </form>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php require __DIR__ . '/../layout/footer.php'; ?>
