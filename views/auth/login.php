<?php /** @var string|null $error */ ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - Inventory & Order Management</title>
</head>
<body>
    <h1>Login</h1>

    <?php if ($error): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST" action="/login">
        <label>
            Email
            <input type="email" name="email" required>
        </label>
        <br>
        <label>
            Password
            <input type="password" name="password" required>
        </label>
        <br>
        <button type="submit">Login</button>
    </form>

    <p><small>Demo: admin@demo.test / admin123</small></p>
</body>
</html>
