<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tyre_maintenance', function (Blueprint $table) {
            $table->id();
            $table->string('maintenance_no')->unique();
            $table->foreignId('tyre_id')->constrained('tyres');
            $table->string('asset_type')->nullable();
            $table->unsignedBigInteger('asset_id')->nullable();
            $table->string('position_code')->nullable();
            $table->string('problem_type');
            $table->text('action_taken')->nullable();
            $table->decimal('cost', 12, 2)->nullable();
            $table->string('technician')->nullable();
            $table->date('maintenance_date');
            $table->date('next_inspection_date')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('prepared_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tyre_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tyre_maintenance');
    }
};
