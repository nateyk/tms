<?php

namespace Tests\Feature;

use App\Models\Tyre;
use App\Models\TyreBrand;
use App\Models\TyreSize;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TyreRegistrationAutoCodeTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->adminUser = User::query()
            ->where('email', 'admin@menkem.com')
            ->firstOrFail();
    }

    public function test_tyre_registration_generates_code_when_not_submitted(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('tyres.store'), $this->payload([
                'serial_number' => 'SN-AUTO-CODE-001',
            ]));

        $response->assertRedirect();

        $tyre = Tyre::query()->where('serial_number', 'SN-AUTO-CODE-001')->firstOrFail();

        $this->assertMatchesRegularExpression('/^TYR-\d{4,}$/', $tyre->tyre_code);
    }

    public function test_tyre_registration_ignores_user_submitted_code(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('tyres.store'), $this->payload([
                'tyre_code' => 'USER-TYPED-CODE',
                'serial_number' => 'SN-AUTO-CODE-002',
            ]));

        $response->assertRedirect();

        $tyre = Tyre::query()->where('serial_number', 'SN-AUTO-CODE-002')->firstOrFail();

        $this->assertNotSame('USER-TYPED-CODE', $tyre->tyre_code);
        $this->assertMatchesRegularExpression('/^TYR-\d{4,}$/', $tyre->tyre_code);
    }

    /** @param array<string, mixed> $overrides */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'serial_number' => 'SN-AUTO-CODE',
            'brand_id' => TyreBrand::query()->first()?->id,
            'size_id' => TyreSize::query()->first()?->id,
            'pattern' => 'Highway',
            'supplier' => 'Demo Supplier',
            'source' => 'purchased_new_tyre',
            'purchase_date' => now()->toDateString(),
            'purchase_price' => 45000,
            'invoice_number' => 'INV-AUTO-CODE',
            'initial_tread_depth' => 16,
            'current_tread_depth' => 16,
            'notes' => null,
        ], $overrides);
    }
}
