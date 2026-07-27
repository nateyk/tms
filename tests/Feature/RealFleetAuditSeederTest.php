<?php

namespace Tests\Feature;

use App\Models\Tyre;
use App\Models\Vehicle;
use Database\Seeders\RealFleetAuditSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RealFleetAuditSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_adds_all_supplied_audits_without_replacing_existing_combinations(): void
    {
        $this->seed(RealFleetAuditSeeder::class);

        $this->assertSame(95, Tyre::query()->count());
        $this->assertSame(8, Vehicle::query()->count());
        $this->assertSame(184142, Vehicle::query()->where('plate_number', 'ET-3-A14761')->value('odometer'));
        $this->assertSame(178505, Vehicle::query()->where('plate_number', 'ET-3-A14763')->value('odometer'));
        $this->assertDatabaseHas('tyres', ['serial_number' => 'KE04157E204']);
        $this->assertDatabaseHas('tyres', ['serial_number' => 'KB07235E901']);
    }
}
