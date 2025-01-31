<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('neon_commands', function (Blueprint $table) {
            $table->boolean('is_embed')->default(false)->after('is_public');
            $table->string('embed_title')->nullable()->after('is_embed');
            $table->text('embed_description')->nullable()->after('embed_title');
            $table->unsignedBigInteger('embed_color')->nullable()->after('embed_description');
            $table->string('guild_id')->change();
        });
    }

    public function down(): void
    {
        Schema::table('neon_commands', function (Blueprint $table) {
            $table->dropColumn('is_embed');
            $table->dropColumn('embed_title');
            $table->dropColumn('embed_description');
            $table->dropColumn('embed_color');
            $table->string('guild_id')->nullable()->change();
        });
    }
};
