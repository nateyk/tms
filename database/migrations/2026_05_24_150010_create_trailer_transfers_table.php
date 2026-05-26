<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trailer_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_no')->unique();
            $table->foreignId('trailer_vehicle_id')->constrained('vehicles');
            $table->foreignId('from_power_vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('to_power_vehicle_id')->constrained('vehicles');
            $table->date('transfer_date');
            $table->unsignedBigInteger('from_odometer')->nullable();
            $table->unsignedBigInteger('to_odometer')->nullable();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('prepared_by')->constrained('users');
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['trailer_vehicle_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trailer_transfers');
    }
};
