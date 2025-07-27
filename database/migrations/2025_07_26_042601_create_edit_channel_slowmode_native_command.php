<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'edit-channel-slowmode'], [
            'slug' => 'edit-channel-slowmode',
            'description' => 'Edits channel slowmode settings.',
            'class' => \App\Jobs\NativeCommand\ProcessEditChannelSlowmodeJob::class,
            'usage' => 'Usage: !edit-channel-slowmode <channel-id> <seconds>',
            'example' => 'Example: !edit-channel-slowmode 123456789012345678 30',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'edit-channel-slowmode')->delete();
    }
};
