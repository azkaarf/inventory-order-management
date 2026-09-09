<?php

namespace App\Repository;

use App\Entity\User;
use App\Support\Database;

final class MySqlUserRepository implements UserRepositoryInterface
{
    public function findByEmail(string $email): ?User
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, name, email, password_hash, role, is_active FROM user WHERE email = ? LIMIT 1'
        );
        $stmt->execute([$email]);
        $row = $stmt->fetch();

        return $row ? $this->toEntity($row) : null;
    }

    public function findById(int $id): ?User
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, name, email, password_hash, role, is_active FROM user WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? $this->toEntity($row) : null;
    }

    private function toEntity(array $row): User
    {
        return new User(
            id: (int) $row['id'],
            name: $row['name'],
            email: $row['email'],
            passwordHash: $row['password_hash'],
            role: $row['role'],
            isActive: (bool) $row['is_active'],
        );
    }
}
