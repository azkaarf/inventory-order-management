<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\UserService;
use App\Support\AuthGuard;

final class UserController
{
    public function __construct(
        private readonly UserService $userService,
    ) {
    }

    public function index(): void
    {
        AuthGuard::requireRole(['Admin']);

        $users = $this->userService->listUsers();
        require __DIR__ . '/../../views/users/index.php';
    }

    public function showCreateForm(): void
    {
        AuthGuard::requireRole(['Admin']);

        $errors = [];
        $old = ['name' => '', 'email' => '', 'role' => 'Sales'];
        require __DIR__ . '/../../views/users/create.php';
    }

    public function create(): void
    {
        AuthGuard::requireRole(['Admin']);

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $role = $_POST['role'] ?? '';

        $errors = $this->userService->validateForCreate($name, $email, $password, $role);

        if (!empty($errors)) {
            // VAL-01: input yang sudah diisi dipertahankan supaya user tidak perlu ngetik ulang
            $old = ['name' => $name, 'email' => $email, 'role' => $role];
            require __DIR__ . '/../../views/users/create.php';
            return;
        }

        $this->userService->createUser($name, $email, $password, $role);
        header('Location: /users');
        exit;
    }

    public function showEditForm(): void
    {
        AuthGuard::requireRole(['Admin']);

        $user = $this->findUserOrFail((int) ($_GET['id'] ?? 0));
        $errors = [];
        $old = ['name' => $user->name, 'email' => $user->email, 'role' => $user->role];
        require __DIR__ . '/../../views/users/edit.php';
    }

    public function update(): void
    {
        AuthGuard::requireRole(['Admin']);

        $id = (int) ($_POST['id'] ?? 0);
        $user = $this->findUserOrFail($id);

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? '';

        $errors = $this->userService->validateForUpdate($id, $name, $email, $role);

        if (!empty($errors)) {
            $old = ['name' => $name, 'email' => $email, 'role' => $role];
            require __DIR__ . '/../../views/users/edit.php';
            return;
        }

        $this->userService->updateUser($id, $name, $email, $role);
        header('Location: /users');
        exit;
    }

    public function toggleActive(): void
    {
        AuthGuard::requireRole(['Admin']);

        $id = (int) ($_POST['id'] ?? 0);
        $active = (int) ($_POST['active'] ?? 0) === 1;

        $this->userService->setActive($id, $active);

        header('Location: /users');
        exit;
    }

    private function findUserOrFail(int $id): User
    {
        $user = $this->userService->findById($id);

        if ($user === null) {
            http_response_code(404);
            echo '404 Not Found — user tidak ditemukan.';
            exit;
        }

        return $user;
    }
}
