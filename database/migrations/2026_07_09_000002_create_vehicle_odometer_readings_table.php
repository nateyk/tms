<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_odometer_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles');
            $table->unsignedBigInteger('odometer');
            $table->date('reading_date');
            $table->string('source')->default('manual');
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['vehicle_id', 'reading_date']);
            $table->index('odometer');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_odometer_readings');
    }
};
