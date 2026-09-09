<?php

namespace App\Controller;

use App\Service\AuthService;
use App\Support\AuthGuard;

final class AuthController
{
    public function __construct(
        private readonly AuthService $authService,
    ) {
    }

    public function showLoginForm(): void
    {
        $error = isset($_GET['error']) ? 'Email atau password salah.' : null;
        require __DIR__ . '/../../views/auth/login.php';
    }

    public function login(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        $user = $this->authService->attemptLogin($email, $password);

        if ($user === null) {
            header('Location: /login?error=1');
            exit;
        }

        // AUTH-01: ID session diperbarui setelah login berhasil
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => $user->id,
            'name' => $user->name,
            'role' => $user->role,
        ];

        header('Location: /dashboard');
        exit;
    }

    public function logout(): void
    {
        // AUTH-02: hapus seluruh data autentikasi pada session
        $_SESSION = [];
        session_destroy();

        header('Location: /login');
        exit;
    }

    public function dashboard(): void
    {
        $user = AuthGuard::requireLogin();
        require __DIR__ . '/../../views/dashboard/placeholder.php';
    }
}
