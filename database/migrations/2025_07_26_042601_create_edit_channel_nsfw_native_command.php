<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'edit-channel-nsfw'], [
            'slug' => 'edit-channel-nsfw',
            'description' => 'Edits channel NSFW settings.',
            'class' => \App\Jobs\NativeCommand\ProcessEditChannelNSFWJob::class,
            'usage' => 'Usage: !edit-channel-nsfw <channel-id> <true|false>',
            'example' => 'Example: !edit-channel-nsfw 123456789012345678 true',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'edit-channel-nsfw')->delete();
    }
};
