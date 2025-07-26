<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'assign-channel'], [
            'slug' => 'assign-channel',
            'description' => 'Assigns a channel to a category.',
            'class' => \App\Jobs\ProcessAssignChannelJob::class,
            'usage' => 'Usage: !assign-channel <channel-id|channel-name> <category-id|category-name>',
            'example' => 'Example: !assign-channel 123456789012345678 987654321098765432',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'assign-channel')->delete();
    }
};
