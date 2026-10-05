<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\UserService;
use App\Support\AuthGuard;

final class UserController extends BaseController
{
    private const INDEX_URL = '/users';

    public function __construct(
        private readonly UserService $userService,
    ) {
    }

    public function index(): void
    {
        AuthGuard::requireRole(self::ROLE_ADMIN);

        $this->render('users/index', [
            'users' => $this->userService->listUsers(),
            'error' => $_GET['error'] ?? null,
        ]);
    }

    public function showCreateForm(): void
    {
        AuthGuard::requireRole(self::ROLE_ADMIN);

        $this->render('users/create', [
            'errors' => [],
            'old' => ['name' => '', 'email' => '', 'role' => 'Sales'],
        ]);
    }

    public function create(): void
    {
        AuthGuard::requireRole(self::ROLE_ADMIN);

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $role = $_POST['role'] ?? '';

        $errors = $this->userService->validateForCreate($name, $email, $password, $role);

        if (!empty($errors)) {
            // VAL-01: entered input is preserved so the user doesn't have to retype it
            $this->render('users/create', [
                'errors' => $errors,
                'old' => ['name' => $name, 'email' => $email, 'role' => $role],
            ]);
            return;
        }

        $this->userService->createUser($name, $email, $password, $role);
        $this->redirect(self::INDEX_URL);
    }

    public function showEditForm(): void
    {
        AuthGuard::requireRole(self::ROLE_ADMIN);

        $user = $this->findUserOrFail((int) ($_GET['id'] ?? 0));

        $this->render('users/edit', [
            'user' => $user,
            'errors' => [],
            'old' => ['name' => $user->name, 'email' => $user->email, 'role' => $user->role],
        ]);
    }

    public function update(): void
    {
        AuthGuard::requireRole(self::ROLE_ADMIN);

        $id = (int) ($_POST['id'] ?? 0);
        $user = $this->findUserOrFail($id);

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? '';

        $errors = $this->userService->validateForUpdate($id, $name, $email, $role);

        if (!empty($errors)) {
            $this->render('users/edit', [
                'user' => $user,
                'errors' => $errors,
                'old' => ['name' => $name, 'email' => $email, 'role' => $role],
            ]);
            return;
        }

        $this->userService->updateUser($id, $name, $email, $role);
        $this->redirect(self::INDEX_URL);
    }

    public function toggleActive(): void
    {
        AuthGuard::requireRole(self::ROLE_ADMIN);

        $id = (int) ($_POST['id'] ?? 0);
        $active = (int) ($_POST['active'] ?? 0) === 1;

        if (!$this->userService->setActive($id, $active)) {
            $this->redirect(self::INDEX_URL . '?error=last-admin');
        }

        $this->redirect(self::INDEX_URL);
    }

    private function findUserOrFail(int $id): User
    {
        $user = $this->userService->findById($id);

        if ($user === null) {
            $this->abort(404, '404 Not Found — user not found.');
        }

        return $user;
    }
}
