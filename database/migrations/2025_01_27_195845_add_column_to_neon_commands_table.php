<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('neon_commands', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
            $table->dropColumn('name');
            $table->text('response')->nullable()->after('description');
            $table->string('command')->after('id')->change();
        });
    }

    public function down(): void
    {
        Schema::table('neon_commands', function (Blueprint $table) {
            $table->string('name')->nullable();
            $table->string('description')->nullable()->change();
            $table->dropColumn('response');
            $table->string('command')->after('description')->change();
        });
    }
};
