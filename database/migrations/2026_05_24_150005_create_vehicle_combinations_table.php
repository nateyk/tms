<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_combinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('power_vehicle_id')->constrained('vehicles');
            $table->foreignId('trailer_vehicle_id')->constrained('vehicles');
            $table->date('attached_date');
            $table->date('detached_date')->nullable();
            $table->unsignedBigInteger('odometer_at_attach')->nullable();
            $table->unsignedBigInteger('odometer_at_detach')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('attached_by')->constrained('users');
            $table->foreignId('detached_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['trailer_vehicle_id', 'status']);
            $table->index(['power_vehicle_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_combinations');
    }
};
