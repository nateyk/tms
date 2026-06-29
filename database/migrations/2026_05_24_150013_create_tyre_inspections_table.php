<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tyre_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tyre_id')->constrained('tyres');
            $table->date('inspection_date');
            $table->decimal('tread_depth', 8, 2)->nullable();
            $table->decimal('pressure', 8, 2)->nullable();
            $table->string('condition')->nullable();
            $table->string('inspector')->nullable();
            $table->foreignId('inspected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tyre_inspections');
    }
};
