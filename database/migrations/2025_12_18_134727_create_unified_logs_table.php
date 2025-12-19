<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('unified_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->onDelete('cascade');
            $table->enum('log_type', [
                'activity',      // login, logout, access
                'audit_trail',   // create, update, delete data
                'security',      // error, failed login
                'system',        // email, wa, cron
                'custom'         // custom log
            ]);
            $table->json('payload');

            // Hash Chain untuk tamper-proof
            $table->string('hash', 64);
            $table->string('prev_hash', 64)->nullable();

            // Metadata request
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            // Indexes untuk performa
            $table->timestamp('created_at')->useCurrent();

            $table->index(['application_id', 'created_at']);
            $table->index('log_type');
            $table->index('hash');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unified_logs');
    }
};
