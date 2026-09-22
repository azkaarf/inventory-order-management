<?php
/** @var string[] $errors */
/** @var array $old */
/** @var \App\Entity\Category $category */
$pageTitle = 'Edit Category';
require __DIR__ . '/../layout/header.php';
?>
<h1>Edit Category</h1>
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

<form method="POST" action="/categories/edit" class="stacked">
                <?= \App\Support\Csrf::field() ?>
    <input type="hidden" name="id" value="<?= $category->id ?>">
    <label>Name <input type="text" name="name" value="<?= htmlspecialchars($old['name']) ?>" required></label>
    <label>Description <input type="text" name="description" value="<?= htmlspecialchars($old['description'] ?? '') ?>"></label>
    <button type="submit">Save</button>
</form>

<?php require __DIR__ . '/../layout/footer.php'; ?>
