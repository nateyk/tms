<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tyre_inspections', function (Blueprint $table) {
            $table->decimal('audited_remaining_percentage', 5, 2)->nullable()->after('pressure');
            $table->decimal('calculated_remaining_percentage_at_audit', 5, 2)->nullable()->after('audited_remaining_percentage');
            $table->integer('audit_odometer')->nullable()->after('calculated_remaining_percentage_at_audit');
        });
    }

    public function down(): void
    {
        Schema::table('tyre_inspections', function (Blueprint $table) {
            $table->dropColumn([
                'audited_remaining_percentage',
                'calculated_remaining_percentage_at_audit',
                'audit_odometer',
            ]);
        });
    }
};
