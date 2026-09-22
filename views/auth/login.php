<?php
/** @var string|null $error */
$pageTitle = 'Login';
require __DIR__ . '/../layout/header.php';
?>
<h1>Login</h1>

<?php if ($error): ?>
    <div class="alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" action="/login" class="stacked">
                <?= \App\Support\Csrf::field() ?>
    <label>
        Email
        <input type="email" name="email" required>
    </label>
    <label>
        Password
        <input type="password" name="password" required>
    </label>
    <button type="submit">Login</button>
</form>

<p class="hint">Demo: admin@demo.test / admin123</p>

<?php require __DIR__ . '/../layout/footer.php'; ?>
