<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tyre_baselines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tyre_id')->constrained('tyres')->unique();
            $table->string('baseline_location_type');
            $table->unsignedBigInteger('baseline_location_id')->nullable();
            $table->string('baseline_position_code')->nullable();
            $table->unsignedBigInteger('baseline_odometer')->nullable();
            $table->decimal('baseline_percentage', 5, 2)->default(100.00);
            $table->unsignedBigInteger('expected_life_km')->default(100000);
            $table->date('baseline_date');
            $table->foreignId('created_by')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('tyre_id');
            $table->index(['baseline_location_type', 'baseline_location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tyre_baselines');
    }
};
