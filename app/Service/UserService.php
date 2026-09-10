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
     * @return string[] daftar pesan error (kosong kalau valid)
     */
    public function validateForCreate(string $name, string $email, string $password, string $role): array
    {
        $errors = [];

        if (trim($name) === '') {
            $errors[] = 'Nama wajib diisi.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email tidak valid.';
        } elseif ($this->userRepository->emailExists($email)) {
            $errors[] = 'Email sudah terdaftar.';
        }

        if (strlen($password) < 8) {
            $errors[] = 'Password minimal 8 karakter.';
        }

        if (!in_array($role, self::ALLOWED_ROLES, true)) {
            $errors[] = 'Role tidak valid.';
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
            $errors[] = 'Nama wajib diisi.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email tidak valid.';
        } elseif ($this->userRepository->emailExists($email, $id)) {
            $errors[] = 'Email sudah dipakai user lain.';
        }

        if (!in_array($role, self::ALLOWED_ROLES, true)) {
            $errors[] = 'Role tidak valid.';
        }

        return $errors;
    }

    public function updateUser(int $id, string $name, string $email, string $role): void
    {
        $this->userRepository->update($id, $name, $email, $role);
    }

    public function setActive(int $id, bool $isActive): void
    {
        $this->userRepository->setActive($id, $isActive);
    }
}
