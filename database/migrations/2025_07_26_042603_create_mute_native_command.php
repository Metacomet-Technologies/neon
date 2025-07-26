<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'mute'], [
            'slug' => 'mute',
            'description' => 'Mutes a user in voice chat.',
            'class' => \App\Jobs\ProcessMuteUserJob::class,
            'usage' => 'Usage: !mute <user-id> [duration]',
            'example' => 'Example: !mute 123456789012345678 10m',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'mute')->delete();
    }
};
