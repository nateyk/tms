<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('matrix_sync_logs');
    }

    public function down(): void
    {
        Schema::create('matrix_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action_type');
            $table->morphs('syncable');
            $table->json('payload');
            $table->string('status')->default('pending');
            $table->json('response')->nullable();
            $table->unsignedInteger('retry_count')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'action_type']);
        });
    }
};
