<?php
// Smoke-test sementara — akan diganti router asli begitu Fase 1 (Auth) dimulai.
require_once __DIR__ . '/../vendor/autoload.php';

echo "<h1>Inventory &amp; Order Management System</h1>";
echo "<p>Skeleton jalan. PHP version: " . PHP_VERSION . "</p>";

try {
    $pdo = new PDO(
        sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            getenv('DB_HOST'),
            getenv('DB_PORT') ?: 3306,
            getenv('DB_NAME')
        ),
        getenv('DB_USER'),
        getenv('DB_PASSWORD')
    );
    echo "<p>&#9989; Koneksi database berhasil.</p>";
} catch (PDOException $e) {
    echo "<p>&#10060; Koneksi database gagal: " . htmlspecialchars($e->getMessage()) . "</p>";
}
