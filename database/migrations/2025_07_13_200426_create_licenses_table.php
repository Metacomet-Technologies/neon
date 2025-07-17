<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['subscription', 'lifetime']);
            $table->string('stripe_id')->nullable();
            $table->enum('status', ['active', 'parked'])->default('parked');
            $table->string('assigned_guild_id')->nullable();
            $table->timestamp('last_assigned_at')->nullable();
            $table->timestamps();

            // Add indexes for performance
            $table->index(['user_id', 'status']);
            $table->index('assigned_guild_id');
            $table->index('stripe_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
