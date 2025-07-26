<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guilds', function (Blueprint $table) {
            $table->boolean('is_bot_member')->default(false)->after('icon');
            $table->timestamp('bot_joined_at')->nullable()->after('is_bot_member');
            $table->timestamp('bot_left_at')->nullable()->after('bot_joined_at');
            $table->timestamp('last_bot_check_at')->nullable()->after('bot_left_at');

            $table->index('is_bot_member');
            $table->index('last_bot_check_at');
        });
    }

    public function down(): void
    {
        Schema::table('guilds', function (Blueprint $table) {
            $table->dropIndex(['is_bot_member']);
            $table->dropIndex(['last_bot_check_at']);

            $table->dropColumn([
                'is_bot_member',
                'bot_joined_at',
                'bot_left_at',
                'last_bot_check_at',
            ]);
        });
    }
};
