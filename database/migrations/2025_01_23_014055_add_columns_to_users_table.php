<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->default('https://cdn.discordapp.com/embed/avatars/0.png');
            $table->string('discord_id')->nullable()->unique();
            $table->string('access_token')->nullable();
            $table->string('refresh_token')->nullable();
            $table->timestamp('refresh_token_expires_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('avatar');
            $table->dropColumn('discord_id');
            $table->dropColumn('access_token');
            $table->dropColumn('refresh_token');
            $table->dropColumn('refresh_token_expires_at');
        });
    }
};
