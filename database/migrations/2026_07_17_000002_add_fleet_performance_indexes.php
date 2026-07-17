<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tyres', function (Blueprint $table): void {
            $table->index(
                ['current_location_type', 'current_location_id', 'current_position_code'],
                'tyres_location_position_idx'
            );
        });

        Schema::table('tyre_assignments', function (Blueprint $table): void {
            $table->index(['asset_id', 'status'], 'tyre_assignments_asset_status_idx');
            $table->index(['tyre_id', 'status', 'installed_date'], 'tyre_assignments_tyre_status_date_idx');
        });

        Schema::table('tyre_inspections', function (Blueprint $table): void {
            $table->index(
                ['tyre_id', 'inspection_date', 'created_at'],
                'tyre_inspections_latest_idx'
            );
        });

        Schema::table('vehicle_odometer_readings', function (Blueprint $table): void {
            $table->index(
                ['vehicle_id', 'reading_date', 'odometer', 'created_at'],
                'vehicle_odometer_latest_idx'
            );
        });

        Schema::table('tyre_movements', function (Blueprint $table): void {
            $table->index(['status', 'completed_at'], 'tyre_movements_status_completed_idx');
        });
    }

    public function down(): void
    {
        Schema::table('tyres', function (Blueprint $table): void {
            $table->dropIndex('tyres_location_position_idx');
        });

        Schema::table('tyre_assignments', function (Blueprint $table): void {
            $table->dropIndex('tyre_assignments_asset_status_idx');
            $table->dropIndex('tyre_assignments_tyre_status_date_idx');
        });

        Schema::table('tyre_inspections', function (Blueprint $table): void {
            $table->dropIndex('tyre_inspections_latest_idx');
        });

        Schema::table('vehicle_odometer_readings', function (Blueprint $table): void {
            $table->dropIndex('vehicle_odometer_latest_idx');
        });

        Schema::table('tyre_movements', function (Blueprint $table): void {
            $table->dropIndex('tyre_movements_status_completed_idx');
        });
    }
};
