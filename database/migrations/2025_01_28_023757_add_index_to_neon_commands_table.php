<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('neon_commands', function (Blueprint $table) {
            $table->index('guild_id');
            $table->unique(['guild_id', 'command']);
        });
    }

    public function down(): void
    {
        Schema::table('neon_commands', function (Blueprint $table) {
            $table->dropIndex(['guild_id']);
            $table->dropUnique(['guild_id', 'command']);
        });
    }
};
