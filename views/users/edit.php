<?php
/** @var string[] $errors */
/** @var array $old */
/** @var \App\Entity\User $user */
$pageTitle = 'Edit User';
require __DIR__ . '/../layout/header.php';
?>
<h1>Edit User</h1>
<p><a href="/users">&larr; Back</a></p>

<?php if (!empty($errors)): ?>
    <div class="alert-error">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="/users/edit" class="stacked">
                <?= \App\Support\Csrf::field() ?>
    <input type="hidden" name="id" value="<?= $user->id ?>">
    <label>Name <input type="text" name="name" value="<?= htmlspecialchars($old['name']) ?>" required></label><br>
    <label>Email <input type="email" name="email" value="<?= htmlspecialchars($old['email']) ?>" required></label><br>
    <label>Role
        <select name="role">
            <option value="Sales" <?= $old['role'] === 'Sales' ? 'selected' : '' ?>>Sales</option>
            <option value="WarehouseStaff" <?= $old['role'] === 'WarehouseStaff' ? 'selected' : '' ?>>Warehouse Staff</option>
            <option value="Admin" <?= $old['role'] === 'Admin' ? 'selected' : '' ?>>Admin</option>
        </select>
    </label><br>
    <button type="submit">Save</button>
</form>

<?php require __DIR__ . '/../layout/footer.php'; ?>
