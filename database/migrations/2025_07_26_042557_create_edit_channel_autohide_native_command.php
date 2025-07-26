<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'edit-channel-autohide'], [
            'slug' => 'edit-channel-autohide',
            'description' => 'Edits channel autohide settings.',
            'class' => \App\Jobs\NativeCommand\ProcessEditChannelAutohideJob::class,
            'usage' => 'Usage: !edit-channel-autohide <channel-id> <minutes [60, 1440, 4320, 10080]>',
            'example' => 'Example: !edit-channel-autohide 123456789012345678 1440',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'edit-channel-autohide')->delete();
    }
};
