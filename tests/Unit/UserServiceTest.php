<?php

namespace Tests\Unit;

use App\Repository\InMemoryUserRepository;
use App\Service\UserService;
use PHPUnit\Framework\TestCase;

final class UserServiceTest extends TestCase
{
    public function test_validasi_gagal_kalau_email_sudah_dipakai(): void
    {
        $repo = new InMemoryUserRepository();
        $repo->create('User Lama', 'dupe@test.com', password_hash('x', PASSWORD_DEFAULT), 'Sales');

        $service = new UserService($repo);
        $errors = $service->validateForCreate('User Baru', 'dupe@test.com', 'password123', 'Sales');

        $this->assertContains('Email sudah terdaftar.', $errors);
    }

    public function test_validasi_gagal_kalau_role_tidak_valid(): void
    {
        $repo = new InMemoryUserRepository();
        $service = new UserService($repo);

        $errors = $service->validateForCreate('User Baru', 'baru@test.com', 'password123', 'SuperAdmin');

        $this->assertContains('Role tidak valid.', $errors);
    }

    public function test_validasi_lolos_dengan_data_benar(): void
    {
        $repo = new InMemoryUserRepository();
        $service = new UserService($repo);

        $errors = $service->validateForCreate('User Baru', 'baru@test.com', 'password123', 'Sales');

        $this->assertEmpty($errors);
    }

    public function test_update_tidak_menganggap_email_sendiri_sebagai_duplikat(): void
    {
        $repo = new InMemoryUserRepository();
        $user = $repo->create('User A', 'a@test.com', password_hash('x', PASSWORD_DEFAULT), 'Sales');

        $service = new UserService($repo);
        $errors = $service->validateForUpdate($user->id, 'User A Updated', 'a@test.com', 'Sales');

        $this->assertEmpty($errors);
    }
}
