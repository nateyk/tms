<?php

namespace Tests\Feature;

use App\Enums\MovementType;
use App\Enums\TyreLocationType;
use App\Enums\TyreSource;
use App\Enums\TyreStatus;
use App\Enums\VoucherStatus;
use App\Models\Tyre;
use App\Models\TyreMovement;
use App\Models\TrailerTransfer;
use App\Models\User;
use App\Services\VoucherNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoucherNumberGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_movement_number_displays_legacy_number_in_compact_form(): void
    {
        $movement = new TyreMovement([
            'movement_no' => 'MOV-20260619-0002',
        ]);

        $this->assertSame('MOV-260619-002', $movement->displayNumber());
    }

    public function test_trailer_transfer_number_displays_legacy_number_in_compact_form(): void
    {
        $transfer = new TrailerTransfer([
            'transfer_no' => 'TRF-20260619-0004',
        ]);

        $this->assertSame('TRF-260619-004', $transfer->displayNumber());
    }

    public function test_generator_uses_compact_date_and_continues_legacy_sequence(): void
    {
        $user = User::factory()->create();
        $tyre = Tyre::query()->create([
            'tyre_code' => 'TYR-NO-001',
            'serial_number' => 'SN-NO-001',
            'source' => TyreSource::PurchasedNewTyre,
            'current_location_type' => TyreLocationType::Store,
            'status' => TyreStatus::Available,
        ]);

        TyreMovement::query()->create([
            'movement_no' => 'MOV-'.now()->format('Ymd').'-0002',
            'movement_type' => MovementType::VehicleToStore,
            'tyre_id' => $tyre->id,
            'to_location_type' => TyreLocationType::Store,
            'movement_date' => now()->toDateString(),
            'status' => VoucherStatus::Draft,
            'prepared_by' => $user->id,
        ]);

        $number = app(VoucherNumberGenerator::class)->generate('MOV', new TyreMovement, 'movement_no');

        $this->assertSame('MOV-'.now()->format('ymd').'-003', $number);
    }
}
