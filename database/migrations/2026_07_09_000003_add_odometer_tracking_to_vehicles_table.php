<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->timestamp('odometer_last_updated_at')->nullable();
            $table->foreignId('odometer_last_updated_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropForeign(['odometer_last_updated_by']);
            $table->dropColumn(['odometer_last_updated_at', 'odometer_last_updated_by']);
        });
    }
};
