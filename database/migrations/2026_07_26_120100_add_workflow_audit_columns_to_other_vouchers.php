<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['trailer_transfers', 'tyre_disposals'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->timestamp('submitted_at')->nullable()->after('approved_by');
                $table->timestamp('checked_at')->nullable()->after('submitted_at');
                $table->timestamp('approved_at')->nullable()->after('checked_at');
                $table->foreignId('voided_by')->nullable()->after('approved_by')->constrained('users')->nullOnDelete();
                $table->timestamp('voided_at')->nullable()->after('completed_at');
                $table->text('void_reason')->nullable()->after('notes');
            });
        }
    }

    public function down(): void
    {
        foreach (['trailer_transfers', 'tyre_disposals'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('voided_by');
                $table->dropColumn(['submitted_at', 'checked_at', 'approved_at', 'voided_at', 'void_reason']);
            });
        }
    }
};
