<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'delete-channel'], [
            'slug' => 'delete-channel',
            'description' => 'Deletes a channel.',
            'class' => \App\Jobs\NativeCommand\ProcessDeleteChannelJob::class,
            'usage' => 'Usage: !delete-channel <channel-id|channel-name>',
            'example' => 'Example: !delete-channel 123456789012345678 or !delete-channel #general',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'delete-channel')->delete();
    }
};
