<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'poll'], [
            'slug' => 'poll',
            'description' => 'Creates a poll for members to vote on.',
            'class' => \App\Jobs\ProcessCreatePollJob::class,
            'usage' => 'Usage: !poll "<question>" "<option1>" "<option2>" ...',
            'example' => 'Example: !poll "What should we do for the event?" "Gaming night" "Movie night" "Q&A session"',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'poll')->delete();
    }
};
