<?php
/** @var string[] $errors */
/** @var array $old */
$pageTitle = 'Add Category';
require __DIR__ . '/../layout/header.php';
?>
<h1>Add Category</h1>
<p><a href="/categories">&larr; Back</a></p>

<?php if (!empty($errors)): ?>
    <div class="alert-error">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="/categories/create" class="stacked">
                <?= \App\Support\Csrf::field() ?>
    <label>Name <input type="text" name="name" value="<?= htmlspecialchars($old['name']) ?>" required></label>
    <label>Description <input type="text" name="description" value="<?= htmlspecialchars($old['description'] ?? '') ?>"></label>
    <button type="submit">Save</button>
</form>

<?php require __DIR__ . '/../layout/footer.php'; ?>
