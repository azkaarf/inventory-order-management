# Refactoring Log

Format tiap entri: smell yang ditemukan, teknik yang dipakai, cuplikan sebelum/sesudah.

## 1. Duplicate Code — boilerplate HTML di setiap view

**Ditemukan saat:** Fase 1 (Auth & User), setelah `login.php`, `placeholder.php` (dashboard),
dan 3 view di `views/users/` semuanya punya blok `<!DOCTYPE html><html>...<head>...`
yang identik, plus link "Kelola User" dan form Logout yang di-copy-paste di beberapa view.

**Teknik:** Extract Method (dalam bentuk partial/include) — pindahkan bagian yang sama
ke `views/layout/header.php` dan `views/layout/footer.php`. Tiap view sekarang tinggal
`require` di awal dan akhir, tidak nulis ulang boilerplate.

**Sebelum** (`views/dashboard/placeholder.php`, dipotong):
```php
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
</head>
<body>
    <h1>Dashboard</h1>
    <p>Halo, ...</p>
    <?php if ($user['role'] === 'Admin'): ?>
        <p><a href="/users">Kelola User</a></p>
    <?php endif; ?>
    <form method="POST" action="/logout">
        <button type="submit">Logout</button>
    </form>
</body>
</html>
```

**Sesudah:**
```php
<?php
$pageTitle = 'Dashboard';
require __DIR__ . '/../layout/header.php';
?>
<h1>Dashboard</h1>
<p>Halo, ...</p>

<?php require __DIR__ . '/../layout/footer.php'; ?>
```

**Efek samping positif:** link navigasi (Dashboard/Kelola User) dan tombol Logout sekarang
cuma didefinisikan sekali di `header.php` (dicek dari role di `$_SESSION['user']`), bukan
diulang manual di tiap view — kalau nanti mau ubah menu, cukup edit 1 file.

<!-- Entri berikutnya ditambah di sini seiring project jalan. Minimal 3 entri total. -->
