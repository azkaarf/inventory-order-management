<?php
/** @var string[] $errors */
/** @var array $old */
$pageTitle = 'Add Supplier';
require __DIR__ . '/../layout/header.php';
?>
<h1>Add Supplier</h1>
<p><a href="/suppliers">&larr; Back</a></p>

<?php if (!empty($errors)): ?>
    <div class="alert-error">
        <ul><?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="POST" action="/suppliers/create" class="stacked">
                <?= \App\Support\Csrf::field() ?>
    <label>Name <input type="text" name="name" value="<?= htmlspecialchars($old['name']) ?>" required></label>
    <label>Contact <input type="text" name="contact" value="<?= htmlspecialchars($old['contact'] ?? '') ?>"></label>
    <label>Address <input type="text" name="address" value="<?= htmlspecialchars($old['address'] ?? '') ?>"></label>
    <button type="submit">Save</button>
</form>

<?php require __DIR__ . '/../layout/footer.php'; ?>
