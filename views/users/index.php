<?php
/** @var \App\Entity\User[] $users */
$pageTitle = 'User Management';
require __DIR__ . '/../layout/header.php';
?>

<?php if (($error ?? null) === 'last-admin'): ?>
    <div class="alert-error">Cannot change/deactivate this role — this account is the only remaining active Admin.</div>
<?php endif; ?>

<p><a href="/users/create">+ Add User</a></p>

<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($users)): ?>
        <tr><td colspan="5">No users yet.</td></tr>
    <?php endif; ?>
    <?php foreach ($users as $user): ?>
        <tr>
            <td><?= htmlspecialchars($user->name) ?></td>
            <td><?= htmlspecialchars($user->email) ?></td>
            <td><?= htmlspecialchars($user->role) ?></td>
            <td><?= \App\Support\StatusBadge::render($user->isActive ? 'Active' : 'Inactive') ?></td>
            <td>
                <div class="icon-btn-group">
                    <a href="/users/edit?id=<?= $user->id ?>" class="icon-btn" title="Edit">✏️</a>
                    <form method="POST" action="/users/toggle">
                    <?= \App\Support\Csrf::field() ?>
                        <input type="hidden" name="id" value="<?= $user->id ?>">
                        <input type="hidden" name="active" value="<?= $user->isActive ? '0' : '1' ?>">
                        <button type="submit" class="icon-btn" title="<?= $user->isActive ? 'Deactivate' : 'Activate' ?>"><?= $user->isActive ? '🚫' : '✅' ?></button>
                    </form>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php require __DIR__ . '/../layout/footer.php'; ?>
