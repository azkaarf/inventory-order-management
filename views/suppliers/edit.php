<?php
/** @var string[] $errors */
/** @var array $old */
/** @var \App\Entity\Supplier $supplier */
$pageTitle = 'Edit Supplier';
require __DIR__ . '/../layout/header.php';
?>
<h1>Edit Supplier</h1>
<p><a href="/suppliers">&larr; Back</a></p>

<?php if (!empty($errors)): ?>
    <div class="alert-error">
        <ul><?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="POST" action="/suppliers/edit" class="stacked">
                <?= \App\Support\Csrf::field() ?>
    <input type="hidden" name="id" value="<?= $supplier->id ?>">
    <label>Name <input type="text" name="name" value="<?= htmlspecialchars($old['name']) ?>" required></label>
    <label>Contact <input type="text" name="contact" value="<?= htmlspecialchars($old['contact'] ?? '') ?>"></label>
    <label>Address <input type="text" name="address" value="<?= htmlspecialchars($old['address'] ?? '') ?>"></label>
    <button type="submit">Save</button>
</form>

<?php require __DIR__ . '/../layout/footer.php'; ?>
