<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('CREATE UNIQUE INDEX tyre_assignments_one_active_per_tyre ON tyre_assignments (tyre_id) WHERE status = \'active\'');
        DB::statement('CREATE UNIQUE INDEX tyre_assignments_one_active_per_position ON tyre_assignments (asset_type, asset_id, position_code) WHERE status = \'active\'');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS tyre_assignments_one_active_per_tyre');
        DB::statement('DROP INDEX IF EXISTS tyre_assignments_one_active_per_position');
    }
};
