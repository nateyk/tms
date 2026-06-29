<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tyre_disposals', function (Blueprint $table) {
            $table->id();
            $table->string('disposal_no')->unique();
            $table->foreignId('tyre_id')->constrained('tyres');
            $table->string('last_location_type')->nullable();
            $table->unsignedBigInteger('last_location_id')->nullable();
            $table->string('last_position_code')->nullable();
            $table->unsignedBigInteger('final_km_used')->nullable();
            $table->string('final_condition')->nullable();
            $table->string('disposal_reason');
            $table->decimal('estimated_scrap_value', 12, 2)->nullable();
            $table->decimal('sold_amount', 12, 2)->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('prepared_by')->constrained('users');
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tyre_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tyre_disposals');
    }
};
