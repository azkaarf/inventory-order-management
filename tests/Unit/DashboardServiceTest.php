<?php

namespace Tests\Unit;

use App\Repository\MySqlDashboardRepository;
use App\Service\DashboardService;
use PHPUnit\Framework\TestCase;

final class DashboardServiceTest extends TestCase
{
    private function makeService(): DashboardService
    {
        // validateDateRange() is pure — it never calls the repository, so a
        // real MySqlDashboardRepository is safe here without a DB connection.
        return new DashboardService(new MySqlDashboardRepository());
    }

    public function test_validation_fails_for_invalid_from_date(): void
    {
        $errors = $this->makeService()->validateDateRange('not-a-date', '2026-01-31');

        $this->assertContains('The "from" date is invalid.', $errors);
    }

    public function test_validation_fails_when_from_is_after_to(): void
    {
        $errors = $this->makeService()->validateDateRange('2026-02-01', '2026-01-01');

        $this->assertContains('The "from" date must not be after the "to" date.', $errors);
    }

    public function test_validation_passes_for_a_valid_range(): void
    {
        $errors = $this->makeService()->validateDateRange('2026-01-01', '2026-01-31');

        $this->assertEmpty($errors);
    }
}
