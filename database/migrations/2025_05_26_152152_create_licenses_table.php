<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Unique license ID

            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('guild_id')->nullable()->index();      // Currently assigned guild
            $table->string('last_assigned_guild_id')->nullable(); // For churn protection
            $table->timestamp('last_assigned_at')->nullable();    // For churn protection

            $table->string('stripe_id')->unique(); // Stripe subscription ID
            $table->string('plan_id')->nullable(); // Optional for plan metadata
            $table->string('stripe_status');

            $table->timestamp('assigned_at')->nullable(); // When activated
            $table->timestamp('ends_at')->nullable();     // Stripe sub end

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
