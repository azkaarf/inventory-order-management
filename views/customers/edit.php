<?php
/** @var string[] $errors */
/** @var array $old */
/** @var \App\Entity\Customer $customer */
$pageTitle = 'Edit Customer';
require __DIR__ . '/../layout/header.php';
?>
<h1>Edit Customer</h1>
<p><a href="/customers">&larr; Back</a></p>

<?php if (!empty($errors)): ?>
    <div class="alert-error">
        <ul><?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="POST" action="/customers/edit" class="stacked">
                <?= \App\Support\Csrf::field() ?>
    <input type="hidden" name="id" value="<?= $customer->id ?>">
    <label>Name <input type="text" name="name" value="<?= htmlspecialchars($old['name']) ?>" required></label>
    <label>Contact <input type="text" name="contact" value="<?= htmlspecialchars($old['contact'] ?? '') ?>"></label>
    <label>Address <input type="text" name="address" value="<?= htmlspecialchars($old['address'] ?? '') ?>"></label>
    <button type="submit">Save</button>
</form>

<?php require __DIR__ . '/../layout/footer.php'; ?>
