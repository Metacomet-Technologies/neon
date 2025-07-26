<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'edit-channel-name'], [
            'slug' => 'edit-channel-name',
            'description' => 'Edits a channel name.',
            'class' => \App\Jobs\ProcessEditChannelNameJob::class,
            'usage' => 'Usage: !edit-channel-name <channel-id> <new-name>',
            'example' => 'Example: !edit-channel-name 123456789012345678 new-channel-name',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'edit-channel-name')->delete();
    }
};
