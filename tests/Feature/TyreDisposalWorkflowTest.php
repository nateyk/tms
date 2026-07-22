<?php

namespace Tests\Feature;

use App\Enums\TyreLocationType;
use App\Enums\TyreStatus;
use App\Enums\VoucherStatus;
use App\Models\Store;
use App\Models\Tyre;
use App\Models\TyreDisposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TyreDisposalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_map_disposal_can_be_created_approved_and_completed(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'admin@menkem.com')->firstOrFail();
        $store = Store::query()->firstOrFail();
        $tyre = Tyre::query()->create([
            'tyre_code' => 'DSP-TYR-0001',
            'serial_number' => 'DSP-SN-0001',
            'current_location_type' => TyreLocationType::Store,
            'current_location_id' => $store->id,
            'status' => TyreStatus::Available,
            'source' => 'purchased_new_tyre',
        ]);

        $this->actingAs($user)
            ->post(route('tyres.disposals.store'), [
                'tyre_id' => $tyre->id,
                'disposal_reason' => 'worn_out',
                'final_condition' => 'Worn below safe limit',
                'disposal_notes' => 'Removed during fleet inspection.',
            ])
            ->assertRedirect();

        $disposal = TyreDisposal::query()->where('tyre_id', $tyre->id)->firstOrFail();
        $this->assertSame(VoucherStatus::Draft, $disposal->status);
        $this->assertSame(TyreLocationType::Store, $disposal->last_location_type);
        $this->assertSame($store->id, $disposal->last_location_id);

        $this->actingAs($user)->post(route('tyres.disposals.submit', $disposal))->assertSessionHas('success');
        $this->actingAs($user)->post(route('tyres.disposals.approve', $disposal))->assertSessionHas('success');
        $this->actingAs($user)->post(route('tyres.disposals.complete', $disposal))->assertSessionHas('success');

        $this->assertSame(VoucherStatus::Completed, $disposal->fresh()->status);
        $this->assertSame(TyreStatus::Disposed, $tyre->fresh()->status);
        $this->assertSame(TyreLocationType::DisposalYard, $tyre->fresh()->current_location_type);
    }

    public function test_tyre_cannot_have_two_active_disposal_vouchers(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'admin@menkem.com')->firstOrFail();
        $store = Store::query()->firstOrFail();
        $tyre = Tyre::query()->create([
            'tyre_code' => 'DSP-TYR-0002',
            'serial_number' => 'DSP-SN-0002',
            'current_location_type' => TyreLocationType::Store,
            'current_location_id' => $store->id,
            'status' => TyreStatus::Available,
            'source' => 'purchased_new_tyre',
        ]);

        $payload = [
            'tyre_id' => $tyre->id,
            'disposal_reason' => 'scrap',
        ];

        $this->actingAs($user)->post(route('tyres.disposals.store'), $payload)->assertRedirect();
        $this->actingAs($user)->post(route('tyres.disposals.store'), $payload)
            ->assertSessionHas('error', 'This tyre already has an active disposal voucher.');

        $this->assertSame(1, TyreDisposal::query()->where('tyre_id', $tyre->id)->count());
    }
}
