<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepositoryInterface;

final class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * AUTH-01: kredensial salah ATAU user nonaktif harus menghasilkan hasil yang
     * sama (null) — supaya pesan errornya generik, tidak membocorkan bagian mana
     * yang salah.
     */
    public function attemptLogin(string $email, string $password): ?User
    {
        $user = $this->userRepository->findByEmail($email);

        if ($user === null || !$user->isActive || !$user->verifyPassword($password)) {
            return null;
        }

        return $user;
    }
}
