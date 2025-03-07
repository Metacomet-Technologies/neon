<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('welcome_settings', function (Blueprint $table) {
            $table->id();
            $table->string('guild_id'); // Discord Server ID
            $table->string('channel_id'); // Channel where the message should be sent
            $table->text('message')->nullable(); // Welcome message content
            $table->boolean('is_active')->default(false); // Whether welcome messages are enabled
            $table->timestamps();

            $table->unique('guild_id'); // Ensure one setting per guild
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('welcome_settings');
    }
};
