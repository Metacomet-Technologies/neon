<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'archive-channel'], [
            'slug' => 'archive-channel',
            'description' => 'Archives or unarchives a channel.',
            'class' => \App\Jobs\NativeCommand\ProcessArchiveChannelJob::class,
            'usage' => 'Usage: !archive-channel <channel-id> <true|false>',
            'example' => 'Example: !archive-channel 123456789012345678 true',
            'is_active' => false,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'archive-channel')->delete();
    }
};
