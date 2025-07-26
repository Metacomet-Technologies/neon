<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add foreign key constraint for licenses.assigned_guild_id -> guilds.id
        Schema::table('licenses', function (Blueprint $table) {
            $table->foreign('assigned_guild_id')->references('id')->on('guilds')->onDelete('set null');
        });

        // Add foreign key constraint for neon_commands.guild_id -> guilds.id
        Schema::table('neon_commands', function (Blueprint $table) {
            $table->foreign('guild_id')->references('id')->on('guilds')->onDelete('cascade');
        });

        // Add foreign key constraint for welcome_settings.guild_id -> guilds.id
        Schema::table('welcome_settings', function (Blueprint $table) {
            $table->foreign('guild_id')->references('id')->on('guilds')->onDelete('cascade');
        });

        // Add foreign key constraint for native_command_requests.guild_id -> guilds.id
        Schema::table('native_command_requests', function (Blueprint $table) {
            $table->foreign('guild_id')->references('id')->on('guilds')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->dropForeign(['assigned_guild_id']);
        });

        Schema::table('neon_commands', function (Blueprint $table) {
            $table->dropForeign(['guild_id']);
        });

        Schema::table('welcome_settings', function (Blueprint $table) {
            $table->dropForeign(['guild_id']);
        });

        Schema::table('native_command_requests', function (Blueprint $table) {
            $table->dropForeign(['guild_id']);
        });
    }
};
