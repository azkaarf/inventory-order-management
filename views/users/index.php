<?php
/** @var \App\Entity\User[] $users */
$pageTitle = 'Manajemen User';
require __DIR__ . '/../layout/header.php';
?>
<h1>Manajemen User</h1>
<p><a href="/users/create">+ Tambah User</a></p>

<table>
    <thead>
        <tr>
            <th>Nama</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($users)): ?>
        <tr><td colspan="5">Belum ada user.</td></tr>
    <?php endif; ?>
    <?php foreach ($users as $user): ?>
        <tr>
            <td><?= htmlspecialchars($user->name) ?></td>
            <td><?= htmlspecialchars($user->email) ?></td>
            <td><?= htmlspecialchars($user->role) ?></td>
            <td><?= $user->isActive ? 'Aktif' : 'Nonaktif' ?></td>
            <td>
                <a href="/users/edit?id=<?= $user->id ?>">Edit</a>
                <form method="POST" action="/users/toggle" style="display:inline">
                    <input type="hidden" name="id" value="<?= $user->id ?>">
                    <input type="hidden" name="active" value="<?= $user->isActive ? '0' : '1' ?>">
                    <button type="submit"><?= $user->isActive ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php require __DIR__ . '/../layout/footer.php'; ?>
