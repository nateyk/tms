<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tyres', function (Blueprint $table) {
            $table->id();
            $table->string('tyre_code')->unique();
            $table->string('serial_number')->unique();
            $table->foreignId('brand_id')->nullable()->constrained('tyre_brands')->nullOnDelete();
            $table->foreignId('size_id')->nullable()->constrained('tyre_sizes')->nullOnDelete();
            $table->string('pattern')->nullable();
            $table->string('supplier')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 12, 2)->default(0);
            $table->string('invoice_number')->nullable();
            $table->decimal('initial_tread_depth', 8, 2)->nullable();
            $table->decimal('current_tread_depth', 8, 2)->nullable();
            $table->string('source');
            $table->string('current_location_type')->default('store');
            $table->unsignedBigInteger('current_location_id')->nullable();
            $table->string('current_position_code')->nullable();
            $table->string('status')->default('pending_approval');
            $table->string('qr_code_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['current_location_type', 'current_location_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tyres');
    }
};
