<?php

namespace Tests\Feature;

use App\Models\VehicleType;
use Database\Seeders\FleetOperationalDefaultsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FleetOperationalDefaultsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_heavy_truck_24_default_layout(): void
    {
        $this->seed(FleetOperationalDefaultsSeeder::class);

        $vehicleType = VehicleType::query()
            ->where('name', 'Heavy truck - 24 tyres (6 axles + W/X spares)')
            ->firstOrFail();

        $this->assertSame('power_vehicle', $vehicleType->asset_type->value);
        $this->assertSame(24, $vehicleType->tyre_count);
        $this->assertSame(6, $vehicleType->axle_count);

        $positions = collect($vehicleType->layout_json['positions'] ?? []);

        $this->assertCount(24, $positions);
        $this->assertTrue($positions->pluck('display_code')->contains('W'));
        $this->assertTrue($positions->pluck('display_code')->contains('X'));
        $this->assertSame(
            range('A', 'V'),
            $positions
                ->pluck('display_code')
                ->reject(fn (string $code) => in_array($code, ['W', 'X'], true))
                ->values()
                ->all()
        );
    }
}
