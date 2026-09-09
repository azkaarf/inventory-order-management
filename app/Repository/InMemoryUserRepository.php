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
}
