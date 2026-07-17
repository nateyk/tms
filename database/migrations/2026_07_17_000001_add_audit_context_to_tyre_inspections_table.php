<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tyre_inspections', function (Blueprint $table): void {
            $table->foreignId('vehicle_id')->nullable()->after('tyre_id')->constrained('vehicles')->nullOnDelete();
            $table->string('position_code')->nullable()->after('vehicle_id');
            $table->decimal('variance_percentage', 5, 2)->nullable()->after('audit_odometer');
            $table->foreignId('audited_by')->nullable()->after('inspected_by')->constrained('users')->nullOnDelete();
            $table->string('reason')->nullable()->after('condition');
        });
    }

    public function down(): void
    {
        Schema::table('tyre_inspections', function (Blueprint $table): void {
            $table->dropForeign(['vehicle_id']);
            $table->dropForeign(['audited_by']);
            $table->dropColumn(['vehicle_id', 'position_code', 'variance_percentage', 'audited_by', 'reason']);
        });
    }
};
