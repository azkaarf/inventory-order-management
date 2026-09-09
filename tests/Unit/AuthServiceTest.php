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

    public function test_login_berhasil_dengan_kredensial_benar(): void
    {
        $repo = new InMemoryUserRepository();
        $repo->add($this->makeUser('a@test.com', 'secret123'));

        $service = new AuthService($repo);
        $user = $service->attemptLogin('a@test.com', 'secret123');

        $this->assertNotNull($user);
        $this->assertSame('a@test.com', $user->email);
    }

    public function test_login_gagal_kalau_password_salah(): void
    {
        $repo = new InMemoryUserRepository();
        $repo->add($this->makeUser('a@test.com', 'secret123'));

        $service = new AuthService($repo);
        $user = $service->attemptLogin('a@test.com', 'password-salah');

        $this->assertNull($user);
    }

    public function test_login_gagal_kalau_user_tidak_aktif(): void
    {
        $repo = new InMemoryUserRepository();
        $repo->add($this->makeUser('a@test.com', 'secret123', active: false));

        $service = new AuthService($repo);
        $user = $service->attemptLogin('a@test.com', 'secret123');

        $this->assertNull($user);
    }

    public function test_login_gagal_kalau_email_tidak_terdaftar(): void
    {
        $repo = new InMemoryUserRepository();

        $service = new AuthService($repo);
        $user = $service->attemptLogin('tidak-ada@test.com', 'secret123');

        $this->assertNull($user);
    }
}
