<?php

namespace Tests\Unit;

use App\Repository\MySqlWarehouseRepository;
use App\Service\WarehouseService;
use PHPUnit\Framework\TestCase;

final class WarehouseServiceTest extends TestCase
{
    /**
     * Note: validate() only checks input and never calls the repository,
     * so it's safe to use MySqlWarehouseRepository here without a real DB
     * connection (Database::connection() is only triggered when a repository
     * method is actually called).
     */
    private function makeService(): WarehouseService
    {
        return new WarehouseService(new MySqlWarehouseRepository());
    }

    public function test_validation_fails_when_name_is_empty(): void
    {
        $errors = $this->makeService()->validate('', 'Jakarta');

        $this->assertContains('Warehouse name is required.', $errors);
    }

    public function test_validation_fails_when_location_is_empty(): void
    {
        $errors = $this->makeService()->validate('Warehouse A', '');

        $this->assertContains('Location is required.', $errors);
    }

    public function test_validation_passes_with_complete_data(): void
    {
        $errors = $this->makeService()->validate('Warehouse A', 'Jakarta');

        $this->assertEmpty($errors);
    }
}
