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

    /** @return User[] */
    public function findAll(): array
    {
        $stmt = Database::connection()->query(
            'SELECT id, name, email, password_hash, role, is_active FROM user ORDER BY name'
        );

        return array_map(fn (array $row) => $this->toEntity($row), $stmt->fetchAll());
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        if ($excludeId === null) {
            $stmt = Database::connection()->prepare('SELECT 1 FROM user WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
        } else {
            $stmt = Database::connection()->prepare('SELECT 1 FROM user WHERE email = ? AND id != ? LIMIT 1');
            $stmt->execute([$email, $excludeId]);
        }

        return (bool) $stmt->fetchColumn();
    }

    public function create(string $name, string $email, string $passwordHash, string $role): User
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO user (name, email, password_hash, role, is_active) VALUES (?, ?, ?, ?, 1)'
        );
        $stmt->execute([$name, $email, $passwordHash, $role]);
        $id = (int) Database::connection()->lastInsertId();

        return new User($id, $name, $email, $passwordHash, $role, true);
    }

    public function update(int $id, string $name, string $email, string $role): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE user SET name = ?, email = ?, role = ? WHERE id = ?'
        );
        $stmt->execute([$name, $email, $role, $id]);
    }

    public function setActive(int $id, bool $isActive): void
    {
        $stmt = Database::connection()->prepare('UPDATE user SET is_active = ? WHERE id = ?');
        $stmt->execute([$isActive ? 1 : 0, $id]);
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
