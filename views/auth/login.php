<?php
/** @var string|null $error */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — BLISS</title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= @filemtime(__DIR__ . '/../../public/assets/css/app.css') ?: time() ?>">
</head>
<body>
<div class="login-shell">
    <div class="login-card">
        <div class="login-logo">BLISS</div>
        <div class="login-tagline">Beauty Logistics, Inventory &amp; Sales System</div>
        <p class="login-subtitle">Please sign in to access your dashboard</p>

        <?php if ($error): ?>
            <div class="alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="/login" class="stacked">
            <?= \App\Support\Csrf::field() ?>
            <label>
                Email
                <input type="email" name="email" autocomplete="username" required autofocus>
            </label>
            <label class="password-field">
                Password
                <input type="password" name="password" id="login-password" autocomplete="current-password" required>
                <button type="button" class="password-toggle" id="login-password-toggle">Show</button>
            </label>
            <div class="login-options">
                <label class="remember-me">
                    <input type="checkbox" name="remember_me" value="1">
                    Remember me
                </label>
            </div>
            <button type="submit">Sign In to Dashboard</button>
        </form>

        <p class="login-demo-hint">Demo: admin@demo.test / admin123</p>
    </div>
</div>
<script>
(() => {
    'use strict';
    const toggle = document.getElementById('login-password-toggle');
    const field = document.getElementById('login-password');
    if (!toggle || !field) {
        return;
    }
    toggle.addEventListener('click', () => {
        const showing = field.type === 'text';
        field.type = showing ? 'password' : 'text';
        toggle.textContent = showing ? 'Show' : 'Hide';
    });
})();
</script>
</body>
</html>
