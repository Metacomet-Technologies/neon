<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'lock-voice'], [
            'slug' => 'lock-voice',
            'description' => 'Locks a voice channel to prevent new members from joining.',
            'class' => \App\Jobs\ProcessLockVoiceChannelJob::class,
            'usage' => 'Usage: !lock-voice <channel-id>',
            'example' => 'Example: !lock-voice 123456789012345678',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'lock-voice')->delete();
    }
};
