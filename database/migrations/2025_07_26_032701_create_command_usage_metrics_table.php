<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('command_usage_metrics', function (Blueprint $table) {
            $table->id();

            // Command identification
            $table->string('command_type')->index(); // 'native' or 'neon'
            $table->string('command_slug')->index(); // e.g., 'ban', 'kick', 'help'
            $table->string('command_hash', 64)->index(); // SHA256 hash for parameter patterns

            // Context (tokenized for privacy)
            $table->string('guild_id')->index();
            $table->string('user_hash', 64)->index(); // Hashed Discord user ID for privacy
            $table->string('channel_type', 20)->nullable(); // 'text', 'voice', 'thread', etc.

            // Usage patterns for research
            $table->json('parameter_signature')->nullable(); // Tokenized parameter types/patterns
            $table->integer('parameter_count')->default(0);
            $table->boolean('had_errors')->default(false);
            $table->string('execution_duration_ms')->nullable();

            // Time-based analytics
            $table->timestamp('executed_at')->index();
            $table->date('date')->index(); // For daily aggregations
            $table->tinyInteger('hour')->index(); // 0-23 for hourly patterns
            $table->tinyInteger('day_of_week')->index(); // 0-6 for weekly patterns

            // Success tracking
            $table->enum('status', ['success', 'failed', 'timeout'])->index();
            $table->string('error_category', 50)->nullable()->index(); // 'permissions', 'rate_limit', 'invalid_params', etc.

            $table->timestamps();

            // Composite indexes for common queries
            $table->index(['command_slug', 'date']);
            $table->index(['guild_id', 'date']);
            $table->index(['command_type', 'status', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('command_usage_metrics');
    }
};
