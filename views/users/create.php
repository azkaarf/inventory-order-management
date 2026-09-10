<?php
/** @var string[] $errors */
/** @var array $old */
$pageTitle = 'Tambah User';
require __DIR__ . '/../layout/header.php';
?>
<h1>Tambah User</h1>
<p><a href="/users">&larr; Kembali</a></p>

<?php if (!empty($errors)): ?>
    <div class="alert-error">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="/users/create" class="stacked">
    <label>Nama <input type="text" name="name" value="<?= htmlspecialchars($old['name']) ?>" required></label>
    <label>Email <input type="email" name="email" value="<?= htmlspecialchars($old['email']) ?>" required></label>
    <label>Password <input type="password" name="password" required></label>
    <label>Role
        <select name="role">
            <option value="Sales" <?= $old['role'] === 'Sales' ? 'selected' : '' ?>>Sales</option>
            <option value="WarehouseStaff" <?= $old['role'] === 'WarehouseStaff' ? 'selected' : '' ?>>Warehouse Staff</option>
            <option value="Admin" <?= $old['role'] === 'Admin' ? 'selected' : '' ?>>Admin</option>
        </select>
    </label>
    <button type="submit">Simpan</button>
</form>

<?php require __DIR__ . '/../layout/footer.php'; ?>
