<?php
/** @var string[] $errors */
/** @var array $old */
/** @var \App\Entity\Warehouse $warehouse */
$pageTitle = 'Edit Warehouse';
require __DIR__ . '/../layout/header.php';
?>
<h1>Edit Warehouse</h1>
<p><a href="/warehouses">&larr; Back</a></p>

<?php if (!empty($errors)): ?>
    <div class="alert-error">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="/warehouses/edit" class="stacked">
                <?= \App\Support\Csrf::field() ?>
    <input type="hidden" name="id" value="<?= $warehouse->id ?>">
    <label>Name <input type="text" name="name" value="<?= htmlspecialchars($old['name']) ?>" required></label>
    <label>Location <input type="text" name="location" value="<?= htmlspecialchars($old['location']) ?>" required></label>
    <button type="submit">Save</button>
</form>

<?php require __DIR__ . '/../layout/footer.php'; ?>
