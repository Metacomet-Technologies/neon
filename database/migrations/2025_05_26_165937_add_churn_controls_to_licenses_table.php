<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->timestamp('last_moved_at')->nullable()->after('assigned_at');
            $table->string('previous_guild_id')->nullable()->after('guild_id');
        });
    }

    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->dropColumn(['last_moved_at', 'previous_guild_id']);
        });
    }
};
