<?php

namespace App\Support;

final class AuthGuard
{
    /**
     * Pastikan user sudah login. Kalau belum, redirect ke halaman login dan
     * hentikan eksekusi. Panggil ini di awal method Controller untuk halaman
     * terproteksi (AUTH-01: halaman terlindungi tidak dapat diakses tanpa session).
     */
    public static function requireLogin(): array
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }

        return $_SESSION['user'];
    }

    /**
     * Sama seperti requireLogin(), tapi sekaligus cek role. Dipakai nanti untuk
     * membedakan halaman Admin/Sales/WarehouseStaff (segregation of duties).
     *
     * @param string[] $allowedRoles
     */
    public static function requireRole(array $allowedRoles): array
    {
        $user = self::requireLogin();

        if (!in_array($user['role'], $allowedRoles, true)) {
            http_response_code(403);
            echo '403 Forbidden — kamu tidak punya akses ke halaman ini.';
            exit;
        }

        return $user;
    }
}
