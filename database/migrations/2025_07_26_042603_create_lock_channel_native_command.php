<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'lock-channel'], [
            'slug' => 'lock-channel',
            'description' => 'Locks a channel to prevent members from sending messages.',
            'class' => \App\Jobs\ProcessLockChannelJob::class,
            'usage' => 'Usage: !lock-channel <channel-id>',
            'example' => 'Example: !lock-channel 123456789012345678',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'lock-channel')->delete();
    }
};
