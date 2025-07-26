<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'edit-channel-topic'], [
            'slug' => 'edit-channel-topic',
            'description' => 'Edits a channel topic.',
            'class' => \App\Jobs\ProcessEditChannelTopicJob::class,
            'usage' => 'Usage: !edit-channel-topic <channel-id> <new-topic>',
            'example' => 'Example: !edit-channel-topic 123456789012345678 "This is the new topic for this channel"',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'edit-channel-topic')->delete();
    }
};
