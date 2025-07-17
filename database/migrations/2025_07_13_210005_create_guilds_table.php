<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guilds', function (Blueprint $table) {
            $table->string('id')->primary(); // Discord Guild ID (snowflake)
            $table->string('name');
            $table->string('icon')->nullable();
            $table->timestamps();

            // Add index for faster lookups
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guilds');
    }
};
