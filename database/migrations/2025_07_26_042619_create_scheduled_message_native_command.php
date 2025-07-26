<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'scheduled-message'], [
            'slug' => 'scheduled-message',
            'description' => 'Schedules a message to be sent at a specified time.',
            'class' => \App\Jobs\NativeCommand\ProcessScheduledMessageJob::class,
            'usage' => 'Usage: !scheduled-message <channel-id> <date-time> <message>',
            'example' => 'Example: !scheduled-message 123456789012345678 "2024-01-15 14:30" "Event reminder!"',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'scheduled-message')->delete();
    }
};
