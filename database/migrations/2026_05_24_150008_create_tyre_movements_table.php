<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tyre_movements', function (Blueprint $table) {
            $table->id();
            $table->string('movement_no')->unique();
            $table->string('movement_type');
            $table->foreignId('tyre_id')->constrained('tyres');
            $table->string('from_location_type')->nullable();
            $table->unsignedBigInteger('from_location_id')->nullable();
            $table->string('from_position_code')->nullable();
            $table->unsignedBigInteger('from_odometer')->nullable();
            $table->string('to_location_type')->nullable();
            $table->unsignedBigInteger('to_location_id')->nullable();
            $table->string('to_position_code')->nullable();
            $table->unsignedBigInteger('to_odometer')->nullable();
            $table->date('movement_date');
            $table->text('reason')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('prepared_by')->constrained('users');
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tyre_id', 'status']);
            $table->index('movement_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tyre_movements');
    }
};
