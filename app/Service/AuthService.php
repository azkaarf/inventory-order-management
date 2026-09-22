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
     * AUTH-01: wrong credentials OR an inactive user must produce the same
     * result (null) — so the error message stays generic and doesn't leak
     * which part was wrong.
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
