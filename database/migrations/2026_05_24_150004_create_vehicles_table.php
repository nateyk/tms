<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('vehicle_code')->unique();
            $table->string('plate_number')->nullable();
            $table->string('asset_type');
            $table->foreignId('vehicle_type_id')->constrained('vehicle_types');
            $table->string('status')->default('active');
            $table->foreignId('current_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->unsignedBigInteger('odometer')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['asset_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
