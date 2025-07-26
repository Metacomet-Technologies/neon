<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'new-channel'], [
            'slug' => 'new-channel',
            'description' => 'Creates a new channel.',
            'class' => \App\Jobs\ProcessNewChannelJob::class,
            'usage' => 'Usage: !new-channel <channel-name> [category-id]',
            'example' => 'Example: !new-channel "general-chat" 123456789012345678',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'new-channel')->delete();
    }
};
