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

        // Remember me: re-send the same session cookie with a 30-day expiry
        // instead of the default session-only cookie. Only affects the
        // browser's copy of the cookie - the server-side session itself is
        // identical either way (see docker/uploads.ini for why
        // gc_maxlifetime was raised to match).
        if (!empty($_POST['remember_me'])) {
            setcookie(session_name(), session_id(), [
                'expires' => time() + 60 * 60 * 24 * 30,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

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
