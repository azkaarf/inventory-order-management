<?php

namespace App\Controller;

use App\Service\AuthService;

final class AuthController
{
    public function __construct(
        private readonly AuthService $authService,
    ) {
    }

    public function showLoginForm(): void
    {
        $error = isset($_GET['error']) ? 'Incorrect email or password.' : null;
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

        // AUTH-01: session ID is regenerated after a successful login
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
        // AUTH-02: clear all authentication data from the session
        $_SESSION = [];
        session_destroy();

        header('Location: /login');
        exit;
    }
}
