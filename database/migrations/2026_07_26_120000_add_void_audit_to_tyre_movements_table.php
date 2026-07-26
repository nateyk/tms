<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tyre_movements', function (Blueprint $table) {
            $table->foreignId('voided_by')->nullable()->after('approved_by')->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable()->after('completed_at');
            $table->text('void_reason')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('tyre_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('voided_by');
            $table->dropColumn(['voided_at', 'void_reason']);
        });
    }
};
