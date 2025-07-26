<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'neon'], [
            'slug' => 'neon',
            'description' => 'AI-powered assistant using ChatGPT.',
            'class' => \App\Jobs\NativeCommand\ProcessNeonChatGPTJob::class,
            'usage' => 'Usage: !neon <your question or request>',
            'example' => 'Example: !neon What is the weather like today?',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'neon')->delete();
    }
};
