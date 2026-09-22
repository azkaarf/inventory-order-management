<?php

namespace App\Support;

final class AuthGuard
{
    /**
     * Make sure the user is logged in. If not, redirect to the login page and
     * stop execution. Call this at the start of a Controller method for any
     * protected page (AUTH-01: protected pages cannot be accessed without a session).
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
     * Same as requireLogin(), but also checks the role. Used to separate
     * Admin/Sales/WarehouseStaff pages (segregation of duties).
     *
     * @param string[] $allowedRoles
     */
    public static function requireRole(array $allowedRoles): array
    {
        $user = self::requireLogin();

        if (!in_array($user['role'], $allowedRoles, true)) {
            http_response_code(403);
            echo '403 Forbidden — you do not have access to this page.';
            exit;
        }

        return $user;
    }
}
