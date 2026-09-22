<?php

namespace Tests\Unit;

use App\Repository\InMemoryUserRepository;
use App\Service\UserService;
use PHPUnit\Framework\TestCase;

final class UserServiceTest extends TestCase
{
    public function test_validation_fails_when_email_already_used(): void
    {
        $repo = new InMemoryUserRepository();
        $repo->create('Existing User', 'dupe@test.com', password_hash('x', PASSWORD_DEFAULT), 'Sales');

        $service = new UserService($repo);
        $errors = $service->validateForCreate('New User', 'dupe@test.com', 'password123', 'Sales');

        $this->assertContains('Email is already registered.', $errors);
    }

    public function test_validation_fails_when_role_is_invalid(): void
    {
        $repo = new InMemoryUserRepository();
        $service = new UserService($repo);

        $errors = $service->validateForCreate('New User', 'new@test.com', 'password123', 'SuperAdmin');

        $this->assertContains('Invalid role.', $errors);
    }

    public function test_validation_passes_with_correct_data(): void
    {
        $repo = new InMemoryUserRepository();
        $service = new UserService($repo);

        $errors = $service->validateForCreate('New User', 'new@test.com', 'password123', 'Sales');

        $this->assertEmpty($errors);
    }

    public function test_update_does_not_treat_own_email_as_duplicate(): void
    {
        $repo = new InMemoryUserRepository();
        $user = $repo->create('User A', 'a@test.com', password_hash('x', PASSWORD_DEFAULT), 'Sales');

        $service = new UserService($repo);
        $errors = $service->validateForUpdate($user->id, 'User A Updated', 'a@test.com', 'Sales');

        $this->assertEmpty($errors);
    }

    public function test_cannot_change_role_of_only_active_admin(): void
    {
        $repo = new InMemoryUserRepository();
        $admin = $repo->create('Only Admin', 'admin@test.com', password_hash('x', PASSWORD_DEFAULT), 'Admin');

        $service = new UserService($repo);
        $errors = $service->validateForUpdate($admin->id, 'Only Admin', 'admin@test.com', 'WarehouseStaff');

        $this->assertContains('Cannot change role — this is the only active Admin account.', $errors);
    }

    public function test_can_change_admin_role_if_another_admin_exists(): void
    {
        $repo = new InMemoryUserRepository();
        $adminOne = $repo->create('Admin One', 'admin1@test.com', password_hash('x', PASSWORD_DEFAULT), 'Admin');
        $repo->create('Admin Two', 'admin2@test.com', password_hash('x', PASSWORD_DEFAULT), 'Admin');

        $service = new UserService($repo);
        $errors = $service->validateForUpdate($adminOne->id, 'Admin One', 'admin1@test.com', 'Sales');

        $this->assertEmpty($errors);
    }

    public function test_cannot_deactivate_only_active_admin(): void
    {
        $repo = new InMemoryUserRepository();
        $admin = $repo->create('Only Admin', 'admin@test.com', password_hash('x', PASSWORD_DEFAULT), 'Admin');

        $service = new UserService($repo);
        $success = $service->setActive($admin->id, false);

        $this->assertFalse($success);
    }
}
