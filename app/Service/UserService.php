<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepositoryInterface;

final class UserService
{
    private const ALLOWED_ROLES = ['Admin', 'Sales', 'WarehouseStaff'];

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    /** @return User[] */
    public function listUsers(): array
    {
        return $this->userRepository->findAll();
    }

    public function findById(int $id): ?User
    {
        return $this->userRepository->findById($id);
    }

    /**
     * @return string[] list of error messages (empty if valid)
     */
    public function validateForCreate(string $name, string $email, string $password, string $role): array
    {
        $errors = [];

        if (trim($name) === '') {
            $errors[] = 'Name is required.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email.';
        } elseif ($this->userRepository->emailExists($email)) {
            $errors[] = 'Email is already registered.';
        }

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }

        if (!in_array($role, self::ALLOWED_ROLES, true)) {
            $errors[] = 'Invalid role.';
        }

        return $errors;
    }

    public function createUser(string $name, string $email, string $password, string $role): User
    {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        return $this->userRepository->create($name, $email, $passwordHash, $role);
    }

    /**
     * @return string[]
     */
    public function validateForUpdate(int $id, string $name, string $email, string $role): array
    {
        $errors = [];

        if (trim($name) === '') {
            $errors[] = 'Name is required.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email.';
        } elseif ($this->userRepository->emailExists($email, $id)) {
            $errors[] = 'Email is already used by another user.';
        }

        if (!in_array($role, self::ALLOWED_ROLES, true)) {
            $errors[] = 'Invalid role.';
        } elseif ($role !== 'Admin' && $this->isLastActiveAdmin($id)) {
            $errors[] = 'Cannot change role — this is the only active Admin account.';
        }

        return $errors;
    }

    public function updateUser(int $id, string $name, string $email, string $role): void
    {
        $this->userRepository->update($id, $name, $email, $role);
    }

    /**
     * @return bool false if this would leave 0 active Admins
     */
    public function setActive(int $id, bool $isActive): bool
    {
        if (!$isActive && $this->isLastActiveAdmin($id)) {
            return false;
        }

        $this->userRepository->setActive($id, $isActive);

        return true;
    }

    private function isLastActiveAdmin(int $id): bool
    {
        $target = $this->userRepository->findById($id);

        if ($target === null || $target->role !== 'Admin' || !$target->isActive) {
            return false;
        }

        $activeAdminCount = count(array_filter(
            $this->userRepository->findAll(),
            fn (User $u) => $u->role === 'Admin' && $u->isActive,
        ));

        return $activeAdminCount <= 1;
    }
}
