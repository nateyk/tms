<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleTyreStatusPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_download_the_reading_monitoring_pdf(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'admin@menkem.com')->firstOrFail();
        $vehicle = Vehicle::query()->where('asset_type', 'power_vehicle')->firstOrFail();

        $response = $this->actingAs($user)
            ->get(route('vouchers.vehicle.tyre-status.pdf', $vehicle));

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload("tyre-status-{$vehicle->vehicle_code}.pdf");

        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_guest_cannot_download_the_reading_monitoring_pdf(): void
    {
        $this->seed();

        $vehicle = Vehicle::query()->where('asset_type', 'power_vehicle')->firstOrFail();

        $this->get(route('vouchers.vehicle.tyre-status.pdf', $vehicle))
            ->assertRedirect(route('login'));
    }
}
