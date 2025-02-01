<?php

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('native_command_parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(NativeCommand::class)
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_required')->default(false);
            $table->integer('order')->default(0);
            $table->jsonb('data_type');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('native_command_parameters');
    }
};
