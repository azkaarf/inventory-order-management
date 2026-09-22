<?php

namespace Tests\Unit;

use App\Entity\User;
use App\Repository\InMemoryUserRepository;
use App\Service\AuthService;
use PHPUnit\Framework\TestCase;

final class AuthServiceTest extends TestCase
{
    private function makeUser(string $email, string $password, bool $active = true): User
    {
        return new User(
            id: 1,
            name: 'Test User',
            email: $email,
            passwordHash: password_hash($password, PASSWORD_DEFAULT),
            role: 'Admin',
            isActive: $active,
        );
    }

    public function test_login_succeeds_with_correct_credentials(): void
    {
        $repo = new InMemoryUserRepository();
        $repo->add($this->makeUser('a@test.com', 'secret123'));

        $service = new AuthService($repo);
        $user = $service->attemptLogin('a@test.com', 'secret123');

        $this->assertNotNull($user);
        $this->assertSame('a@test.com', $user->email);
    }

    public function test_login_fails_when_password_is_wrong(): void
    {
        $repo = new InMemoryUserRepository();
        $repo->add($this->makeUser('a@test.com', 'secret123'));

        $service = new AuthService($repo);
        $user = $service->attemptLogin('a@test.com', 'wrong-password');

        $this->assertNull($user);
    }

    public function test_login_fails_when_user_is_inactive(): void
    {
        $repo = new InMemoryUserRepository();
        $repo->add($this->makeUser('a@test.com', 'secret123', active: false));

        $service = new AuthService($repo);
        $user = $service->attemptLogin('a@test.com', 'secret123');

        $this->assertNull($user);
    }

    public function test_login_fails_when_email_is_not_registered(): void
    {
        $repo = new InMemoryUserRepository();

        $service = new AuthService($repo);
        $user = $service->attemptLogin('not-registered@test.com', 'secret123');

        $this->assertNull($user);
    }
}
