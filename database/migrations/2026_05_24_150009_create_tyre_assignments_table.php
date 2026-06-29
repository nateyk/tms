<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tyre_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tyre_id')->constrained('tyres');
            $table->string('asset_type');
            $table->foreignId('asset_id')->constrained('vehicles');
            $table->string('position_code');
            $table->date('installed_date');
            $table->unsignedBigInteger('installed_odometer')->nullable();
            $table->date('removed_date')->nullable();
            $table->unsignedBigInteger('removed_odometer')->nullable();
            $table->unsignedBigInteger('km_used')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('installed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('removed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('movement_id')->nullable()->constrained('tyre_movements')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tyre_id', 'status']);
            $table->index(['asset_type', 'asset_id', 'position_code', 'status'], 'tyre_assign_asset_pos_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tyre_assignments');
    }
};
