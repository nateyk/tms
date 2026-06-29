<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement(
                "ALTER TABLE tyre_assignments
                    ADD active_tyre_id BIGINT UNSIGNED GENERATED ALWAYS AS
                        (CASE WHEN status = 'active' THEN tyre_id ELSE NULL END) STORED",
            );
            DB::statement(
                "ALTER TABLE tyre_assignments
                    ADD active_position_code VARCHAR(255) GENERATED ALWAYS AS
                        (CASE WHEN status = 'active' THEN position_code ELSE NULL END) STORED",
            );
            DB::statement('CREATE UNIQUE INDEX tyre_assignments_one_active_per_tyre ON tyre_assignments (active_tyre_id)');
            DB::statement('CREATE UNIQUE INDEX tyre_assignments_one_active_per_position ON tyre_assignments (asset_type, asset_id, active_position_code)');

            return;
        }

        DB::statement("CREATE UNIQUE INDEX tyre_assignments_one_active_per_tyre ON tyre_assignments (tyre_id) WHERE status = 'active'");
        DB::statement("CREATE UNIQUE INDEX tyre_assignments_one_active_per_position ON tyre_assignments (asset_type, asset_id, position_code) WHERE status = 'active'");
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('DROP INDEX tyre_assignments_one_active_per_tyre ON tyre_assignments');
            DB::statement('DROP INDEX tyre_assignments_one_active_per_position ON tyre_assignments');
            DB::statement('ALTER TABLE tyre_assignments DROP COLUMN active_tyre_id');
            DB::statement('ALTER TABLE tyre_assignments DROP COLUMN active_position_code');

            return;
        }

        DB::statement('DROP INDEX IF EXISTS tyre_assignments_one_active_per_tyre');
        DB::statement('DROP INDEX IF EXISTS tyre_assignments_one_active_per_position');
    }
};
