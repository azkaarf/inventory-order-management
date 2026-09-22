<?php
/** @var \App\Entity\Customer[] $customers */
$pageTitle = 'Customers';
require __DIR__ . '/../layout/header.php';
?>
<h1>Customers</h1>
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
                <a href="/customers/edit?id=<?= $customer->id ?>" title="Edit">✏️</a>
                <form method="POST" action="/customers/toggle" style="display:inline">
                <?= \App\Support\Csrf::field() ?>
                    <input type="hidden" name="id" value="<?= $customer->id ?>">
                    <input type="hidden" name="active" value="<?= $customer->isActive ? '0' : '1' ?>">
                    <button type="submit" title="<?= $customer->isActive ? 'Deactivate' : 'Activate' ?>"><?= $customer->isActive ? '🚫' : '✅' ?></button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php require __DIR__ . '/../layout/footer.php'; ?>
