<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('native_command_requests', function (Blueprint $table) {
            $table->id();
            $table->string('guild_id');
            $table->string('channel_id');
            $table->string('discord_user_id');
            $table->text('message_content');
            $table->json('command');
            $table->json('additional_parameters')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->json('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('native_command_requests');
    }
};
