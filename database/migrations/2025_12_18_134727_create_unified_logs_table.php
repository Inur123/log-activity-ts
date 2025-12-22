<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unified_logs', function (Blueprint $table) {
            // ✅ UUID as primary key
            $table->uuid('id')->primary();

            $table->foreignId('application_id')->constrained()->onDelete('cascade');

            $table->string('log_type', 100)->index();

            $table->json('payload');
            $table->string('hash', 64)->index();
            $table->string('prev_hash', 64)->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['application_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unified_logs');
    }
};
