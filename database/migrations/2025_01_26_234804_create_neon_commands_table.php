<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('neon_commands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description');
            $table->string('command');
            $table->string('guild_id')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_public')->default(false);
            $table->foreignIdFor(\App\Models\User::class, 'created_by')->constrained()->cascadeOnDelete()->cascadeOnUpdate()->nullable();
            $table->foreignIdFor(\App\Models\User::class, 'updated_by')->constrained()->cascadeOnDelete()->cascadeOnUpdate()->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('neon_commands');
    }
};
