<?php
/** @var \App\Entity\Category[] $categories */
$pageTitle = 'Product Categories';
require __DIR__ . '/../layout/header.php';
?>
<h1>Product Categories</h1>

<?php if (($error ?? null) === 'in-use'): ?>
    <div class="alert-error">This category cannot be deleted — it is still used by a product.</div>
<?php endif; ?>

<p><a href="/categories/create">+ Add Category</a></p>

<table>
    <thead>
        <tr><th>Name</th><th>Description</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php if (empty($categories)): ?>
        <tr><td colspan="3">No categories yet.</td></tr>
    <?php endif; ?>
    <?php foreach ($categories as $category): ?>
        <tr>
            <td><?= htmlspecialchars($category->name) ?></td>
            <td><?= htmlspecialchars($category->description ?? '-') ?></td>
            <td>
                <a href="/categories/edit?id=<?= $category->id ?>" title="Edit">✏️</a>
                <form method="POST" action="/categories/delete" style="display:inline" onsubmit="return confirm('Delete this category?');">
                <?= \App\Support\Csrf::field() ?>
                    <input type="hidden" name="id" value="<?= $category->id ?>">
                    <button type="submit" title="Delete">🗑️</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php require __DIR__ . '/../layout/footer.php'; ?>
