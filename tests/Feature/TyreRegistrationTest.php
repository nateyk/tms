<?php

namespace Tests\Feature;

use App\Enums\TyreStatus;
use App\Models\Tyre;
use App\Models\User;
use App\Services\TyreRegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TyreRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tyre_registration_approval_generates_qr_and_sets_available(): void
    {
        Storage::fake('public');
        $this->seed();

        $user = User::query()->where('email', 'admin@menkem.com')->firstOrFail();

        $tyre = Tyre::query()->create([
            'tyre_code' => 'TYR-TEST-99',
            'serial_number' => 'SN-TEST-99',
            'source' => 'purchased_new_tyre',
            'current_location_type' => 'store',
            'current_location_id' => 1,
            'purchase_price' => 1000,
            'status' => TyreStatus::PendingApproval,
        ]);

        $approved = app(TyreRegistrationService::class)->approve($tyre, $user->id);

        $this->assertEquals(TyreStatus::Available, $approved->status);
        $this->assertNotNull($approved->qr_code_path);
        Storage::disk('public')->assertExists($approved->qr_code_path);
    }
}
