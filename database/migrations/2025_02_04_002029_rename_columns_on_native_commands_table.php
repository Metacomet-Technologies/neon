<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('native_commands', function (Blueprint $table) {
            $table->renameColumn('help', 'usage');
            $table->dropColumn('sample');
        });
    }

    public function down(): void
    {
        Schema::table('native_commands', function (Blueprint $table) {
            $table->renameColumn('usage', 'help');
            $table->text('sample')->nullable();
        });
    }
};
