<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('chassis_number')->nullable()->after('plate_number');
            $table->string('engine_number')->nullable()->after('chassis_number');
            $table->unsignedSmallInteger('manufacture_year')->nullable()->after('engine_number');

            $table->index('plate_number');
            $table->index('chassis_number');
            $table->index('engine_number');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropIndex(['plate_number']);
            $table->dropIndex(['chassis_number']);
            $table->dropIndex(['engine_number']);
            $table->dropColumn(['chassis_number', 'engine_number', 'manufacture_year']);
        });
    }
};
