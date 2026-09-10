<?php
/** @var string[] $errors */
/** @var array $old */
/** @var \App\Entity\User $user */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit User</title>
</head>
<body>
    <h1>Edit User</h1>
    <p><a href="/users">&larr; Kembali</a></p>

    <?php if (!empty($errors)): ?>
        <ul style="color:red;">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="POST" action="/users/edit">
        <input type="hidden" name="id" value="<?= $user->id ?>">
        <label>Nama <input type="text" name="name" value="<?= htmlspecialchars($old['name']) ?>" required></label><br>
        <label>Email <input type="email" name="email" value="<?= htmlspecialchars($old['email']) ?>" required></label><br>
        <label>Role
            <select name="role">
                <option value="Sales" <?= $old['role'] === 'Sales' ? 'selected' : '' ?>>Sales</option>
                <option value="WarehouseStaff" <?= $old['role'] === 'WarehouseStaff' ? 'selected' : '' ?>>Warehouse Staff</option>
                <option value="Admin" <?= $old['role'] === 'Admin' ? 'selected' : '' ?>>Admin</option>
            </select>
        </label><br>
        <button type="submit">Simpan</button>
    </form>
</body>
</html>
