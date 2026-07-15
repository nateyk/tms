<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreAutoCodeTest extends TestCase
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

    public function test_store_create_generates_code_when_not_submitted(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('fleet.stores.store'), $this->payload([
                'name' => 'Auto Store One',
            ]));

        $response->assertRedirect();

        $store = Store::query()->where('name', 'Auto Store One')->firstOrFail();

        $this->assertMatchesRegularExpression('/^STR-\d{4,}$/', $store->code);
    }

    public function test_store_create_ignores_user_submitted_code(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('fleet.stores.store'), $this->payload([
                'code' => 'USER-TYPED-CODE',
                'name' => 'Auto Store Two',
            ]));

        $response->assertRedirect();

        $store = Store::query()->where('name', 'Auto Store Two')->firstOrFail();

        $this->assertNotSame('USER-TYPED-CODE', $store->code);
        $this->assertMatchesRegularExpression('/^STR-\d{4,}$/', $store->code);
    }

    public function test_store_update_does_not_change_code(): void
    {
        $store = Store::query()->create($this->payload([
            'name' => 'Auto Store Three',
        ]));

        $originalCode = $store->code;

        $response = $this->actingAs($this->adminUser)
            ->put(route('fleet.stores.update', $store), $this->payload([
                'code' => 'CHANGED-BY-USER',
                'name' => 'Auto Store Three Updated',
            ]));

        $response->assertRedirect();

        $store->refresh();

        $this->assertSame($originalCode, $store->code);
        $this->assertSame('Auto Store Three Updated', $store->name);
    }

    /** @param array<string, mixed> $overrides */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'code' => '',
            'name' => 'Auto Store',
            'address' => null,
            'phone' => null,
            'is_default' => false,
            'status' => 'active',
            'notes' => null,
        ], $overrides);
    }
}
