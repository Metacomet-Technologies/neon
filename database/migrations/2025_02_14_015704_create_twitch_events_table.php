<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('twitch_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique();
            $table->timestamp('event_timestamp');
            $table->string('event_type');
            $table->json('event_data');
            $table->boolean('is_processed')->default(false);
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('errored_at')->nullable();
            $table->json('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('twitch_events');
    }
};
