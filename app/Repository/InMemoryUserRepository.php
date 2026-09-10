<?php

namespace App\Repository;

use App\Entity\User;

/**
 * Fake repository untuk unit test — tidak menyentuh database sungguhan.
 * Bukti ARCH-01: AuthService bisa diuji tanpa koneksi PDO nyata.
 */
final class InMemoryUserRepository implements UserRepositoryInterface
{
    /** @var array<string, User> */
    private array $users = [];

    private int $nextId = 100;

    public function add(User $user): void
    {
        $this->users[$user->email] = $user;
    }

    public function findByEmail(string $email): ?User
    {
        return $this->users[$email] ?? null;
    }

    public function findById(int $id): ?User
    {
        foreach ($this->users as $user) {
            if ($user->id === $id) {
                return $user;
            }
        }

        return null;
    }

    /** @return User[] */
    public function findAll(): array
    {
        return array_values($this->users);
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        foreach ($this->users as $user) {
            if ($user->email === $email && $user->id !== $excludeId) {
                return true;
            }
        }

        return false;
    }

    public function create(string $name, string $email, string $passwordHash, string $role): User
    {
        $user = new User($this->nextId++, $name, $email, $passwordHash, $role, true);
        $this->users[$user->email] = $user;

        return $user;
    }

    public function update(int $id, string $name, string $email, string $role): void
    {
        $existing = $this->findById($id);
        if ($existing === null) {
            return;
        }

        unset($this->users[$existing->email]);
        $updated = new User($id, $name, $email, $existing->passwordHash, $role, $existing->isActive);
        $this->users[$updated->email] = $updated;
    }

    public function setActive(int $id, bool $isActive): void
    {
        $existing = $this->findById($id);
        if ($existing === null) {
            return;
        }

        unset($this->users[$existing->email]);
        $updated = new User($id, $existing->name, $existing->email, $existing->passwordHash, $existing->role, $isActive);
        $this->users[$updated->email] = $updated;
    }
}
