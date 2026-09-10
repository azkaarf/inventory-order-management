<?php

namespace App\Repository;

use App\Entity\User;

interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?User;

    public function findById(int $id): ?User;

    /** @return User[] */
    public function findAll(): array;

    public function emailExists(string $email, ?int $excludeId = null): bool;

    public function create(string $name, string $email, string $passwordHash, string $role): User;

    public function update(int $id, string $name, string $email, string $role): void;

    public function setActive(int $id, bool $isActive): void;
}
