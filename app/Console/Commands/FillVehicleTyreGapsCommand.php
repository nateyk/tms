<?php

namespace App\Console\Commands;

use App\Enums\MovementType;
use App\Enums\TyreLocationType;
use App\Enums\TyreSource;
use App\Enums\TyreStatus;
use App\Models\Store;
use App\Models\Tyre;
use App\Models\TyreBrand;
use App\Models\TyreSize;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ApprovalService;
use App\Services\TyreMapWorkflowService;
use App\Services\TyreMovementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class FillVehicleTyreGapsCommand extends Command
{
    protected $signature = 'tms:fill-vehicle-gaps
                            {vehicle? : Vehicle code e.g. TRK-001}
                            {--all : Fill gaps on all vehicles with open positions}';

    protected $description = 'Install store tyres into empty positions (full movement workflow)';

    public function handle(
        TyreMapWorkflowService $workflow,
        TyreMovementService $movements,
        ApprovalService $approval,
    ): int {
        $admin = User::query()->where('email', 'admin@menkem.com')->first();
        if (! $admin) {
            $this->error('Admin user not found. Run php artisan migrate:fresh --seed');

            return self::FAILURE;
        }

        Auth::login($admin);

        $vehicles = $this->resolveVehicles();

        if ($vehicles->isEmpty()) {
            $this->warn('No vehicles matched.');

            return self::SUCCESS;
        }

        $filled = 0;

        foreach ($vehicles as $vehicle) {
            $empty = $workflow->emptyPositions($vehicle);
            if ($empty->isEmpty()) {
                $this->line("{$vehicle->vehicle_code}: all positions filled.");

                continue;
            }

            $this->info("{$vehicle->vehicle_code}: filling {$empty->count()} gap(s)...");

            foreach ($empty as $slot) {
                $tyre = $this->acquireStoreTyre($filled);
                $movement = $movements->createDraft([
                    'movement_type' => MovementType::StoreToVehicle->value,
                    'tyre_id' => $tyre->id,
                    'movement_date' => now()->toDateString(),
                    'to_location_type' => $workflow->locationTypeForVehicle($vehicle)->value,
                    'to_location_id' => $vehicle->id,
                    'to_position_code' => $slot['code'],
                    'to_odometer' => $vehicle->odometer,
                    'reason' => "Auto-fill gap at {$slot['code']} on {$vehicle->vehicle_code}",
                ], $admin->id);

                $movement = $approval->submit($movement);
                $movement = $approval->check($movement);
                $movement = $approval->approve($movement);
                $approval->completeMovement($movement);

                $this->line("  ✓ {$slot['code']} ← {$tyre->tyre_code}");
                $filled++;
            }
        }

        $this->newLine();
        $this->info("Completed {$filled} installation(s).");

        return self::SUCCESS;
    }

    /** @return \Illuminate\Support\Collection<int, Vehicle> */
    protected function resolveVehicles()
    {
        if ($this->option('all')) {
            return Vehicle::query()->orderBy('vehicle_code')->get();
        }

        $code = $this->argument('vehicle') ?? 'TRK-001';

        return Vehicle::query()->where('vehicle_code', $code)->get();
    }

    protected function acquireStoreTyre(int $index): Tyre
    {
        $available = Tyre::query()
            ->where('status', TyreStatus::Available)
            ->where('current_location_type', TyreLocationType::Store)
            ->orderBy('tyre_code')
            ->first();

        if ($available) {
            return $available;
        }

        return $this->createStoreTyre($index);
    }

    protected function createStoreTyre(int $index): Tyre
    {
        $store = Store::query()->where('is_default', true)->firstOrFail();
        $brand = TyreBrand::query()->first();
        $size = TyreSize::query()->first();
        do {
            $suffix = strtoupper(substr(uniqid(), -8));
            $code = 'TYR-AUTO-'.$suffix;
        } while (Tyre::query()->where('tyre_code', $code)->exists());

        return Tyre::query()->create([
            'tyre_code' => $code,
            'serial_number' => 'SN-'.$code,
            'brand_id' => $brand?->id,
            'size_id' => $size?->id,
            'purchase_date' => now()->toDateString(),
            'purchase_price' => 45000,
            'initial_tread_depth' => 16.0,
            'current_tread_depth' => 15.0,
            'source' => TyreSource::PurchasedNewTyre,
            'current_location_type' => TyreLocationType::Store,
            'current_location_id' => $store->id,
            'status' => TyreStatus::Available,
        ]);
    }
}
