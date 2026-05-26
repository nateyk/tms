<?php

namespace App\Console\Commands;

use App\Models\VehicleType;
use App\Services\VehicleTyreLayoutBuilder;
use Illuminate\Console\Command;

class RefreshTyreLayoutsCommand extends Command
{
    protected $signature = 'tms:refresh-tyre-layouts';

    protected $description = 'Regenerate axle-based Konva tyre map layouts for all vehicle types';

    public function handle(VehicleTyreLayoutBuilder $builder): int
    {
        $count = 0;

        VehicleType::query()->each(function (VehicleType $type) use ($builder, &$count) {
            $prefix = match ($type->asset_type?->value) {
                'trailer' => 'T',
                'rigid_truck' => 'R',
                default => 'P',
            };

            $type->update([
                'layout_json' => $builder->buildLayout(
                    (int) $type->tyre_count,
                    (int) $type->axle_count,
                    $prefix,
                ),
            ]);

            $count++;
            $this->line("Updated: {$type->name}");
        });

        $this->info("Refreshed {$count} vehicle type layout(s).");

        return self::SUCCESS;
    }
}
