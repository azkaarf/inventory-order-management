<?php

namespace Tests\Unit;

use App\Repository\DashboardRepositoryInterface;
use App\Service\DashboardService;
use PHPUnit\Framework\TestCase;

/**
 * TEST-01 (DASH-01, REPORT-01): DashboardService hanya meneruskan ke repository,
 * jadi yang diuji adalah kontraknya - method yang benar dipanggil dengan
 * argumen yang benar - plus validasi rentang tanggal laporan. Repository
 * adalah mock dari interface, tanpa database.
 */
final class DashboardServiceDelegationTest extends TestCase
{
    public function test_total_inventory_value_comes_from_the_repository(): void
    {
        $repo = $this->createMock(DashboardRepositoryInterface::class);
        $repo->expects($this->once())->method('getTotalInventoryValue')->willReturn(12345.5);

        $this->assertSame(12345.5, (new DashboardService($repo))->totalInventoryValue());
    }

    public function test_low_stock_products_come_from_the_repository(): void
    {
        $rows = [['sku' => 'MUP-003', 'total_stock' => 4, 'reorder_point' => 5]];
        $repo = $this->createMock(DashboardRepositoryInterface::class);
        $repo->expects($this->once())->method('getLowStockProducts')->willReturn($rows);

        $this->assertSame($rows, (new DashboardService($repo))->lowStockProducts());
    }

    public function test_purchase_order_status_counts_come_from_the_repository(): void
    {
        $counts = ['Draft' => 2, 'Ordered' => 1];
        $repo = $this->createMock(DashboardRepositoryInterface::class);
        $repo->expects($this->once())->method('getPurchaseOrderStatusCounts')->willReturn($counts);

        $this->assertSame($counts, (new DashboardService($repo))->purchaseOrderStatusCounts());
    }

    public function test_sales_order_status_counts_cover_all_users_when_no_owner_is_given(): void
    {
        $repo = $this->createMock(DashboardRepositoryInterface::class);
        $repo->expects($this->once())->method('getSalesOrderStatusCounts')->with(null)->willReturn(['Draft' => 3]);

        $this->assertSame(['Draft' => 3], (new DashboardService($repo))->salesOrderStatusCounts());
    }

    public function test_sales_order_status_counts_are_limited_to_the_given_owner(): void
    {
        // Dashboard Sales hanya menghitung order milik user itu sendiri.
        $repo = $this->createMock(DashboardRepositoryInterface::class);
        $repo->expects($this->once())->method('getSalesOrderStatusCounts')->with(7)->willReturn(['Approved' => 1]);

        $this->assertSame(['Approved' => 1], (new DashboardService($repo))->salesOrderStatusCounts(7));
    }

    public function test_warehouse_queues_come_from_the_repository(): void
    {
        $awaitingReceipt = [['id' => 12, 'status' => 'Ordered']];
        $awaitingIssue = [['id' => 10, 'status' => 'Approved']];
        $repo = $this->createStub(DashboardRepositoryInterface::class);
        $repo->method('getPurchaseOrdersAwaitingReceipt')->willReturn($awaitingReceipt);
        $repo->method('getSalesOrdersAwaitingIssue')->willReturn($awaitingIssue);

        $service = new DashboardService($repo);

        $this->assertSame($awaitingReceipt, $service->purchaseOrdersAwaitingReceipt());
        $this->assertSame($awaitingIssue, $service->salesOrdersAwaitingIssue());
    }

    public function test_stock_ledger_report_passes_the_date_range_through(): void
    {
        $rows = [['movement_type' => 'Receipt', 'quantity' => 10]];
        $repo = $this->createMock(DashboardRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('getStockLedgerReport')
            ->with('2026-10-01', '2026-10-31')
            ->willReturn($rows);

        $this->assertSame($rows, (new DashboardService($repo))->stockLedgerReport('2026-10-01', '2026-10-31'));
    }

    public function test_order_status_report_passes_the_date_range_and_owner_through(): void
    {
        $rows = [['type' => 'SO', 'status' => 'Fulfilled']];
        $repo = $this->createMock(DashboardRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('getOrderStatusReport')
            ->with('2026-10-01', '2026-10-31', 7)
            ->willReturn($rows);

        $this->assertSame($rows, (new DashboardService($repo))->orderStatusReport('2026-10-01', '2026-10-31', 7));
    }

    // ---------- validasi rentang tanggal laporan (REPORT-01) ----------

    public function test_date_range_rejects_an_invalid_to_date(): void
    {
        $service = new DashboardService($this->createStub(DashboardRepositoryInterface::class));

        $this->assertSame(['The "to" date is invalid.'], $service->validateDateRange('2026-10-01', 'not-a-date'));
    }

    public function test_date_range_reports_both_dates_when_both_are_invalid(): void
    {
        $service = new DashboardService($this->createStub(DashboardRepositoryInterface::class));

        $errors = $service->validateDateRange('2026-13-45', '');

        $this->assertSame(['The "from" date is invalid.', 'The "to" date is invalid.'], $errors);
    }

    public function test_date_range_accepts_a_single_day(): void
    {
        $service = new DashboardService($this->createStub(DashboardRepositoryInterface::class));

        $this->assertSame([], $service->validateDateRange('2026-10-07', '2026-10-07'));
    }
}
