<?php

namespace Tests\Feature;

use App\Services\TyreReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_report_helpers_return_expected_shapes(): void
    {
        $this->seed();

        $service = app(TyreReportService::class);

        $stats = $service->dashboardStats();
        $this->assertArrayHasKey('total_tyres', $stats);
        $this->assertArrayHasKey('pending_registration', $stats);

        $trend = $service->completedMovementsTrend();
        $this->assertCount(8, $trend['labels']);
        $this->assertCount(8, $trend['data']);

        $location = $service->tyresByLocationChart();
        $this->assertNotEmpty($location['labels']);
        $this->assertSameSize($location['labels'], $location['data']);

        $utilization = $service->fleetPositionUtilization();
        $this->assertArrayHasKey('filled', $utilization);
        $this->assertArrayHasKey('empty', $utilization);
        $this->assertGreaterThan(0, $utilization['filled'] + $utilization['empty']);
    }
}
