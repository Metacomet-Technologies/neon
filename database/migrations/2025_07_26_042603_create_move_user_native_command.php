<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'move-user'], [
            'slug' => 'move-user',
            'description' => 'Moves a user to a different voice channel.',
            'class' => \App\Jobs\ProcessMoveUserJob::class,
            'usage' => 'Usage: !move-user <user-id> <channel-id>',
            'example' => 'Example: !move-user 123456789012345678 987654321098765432',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'move-user')->delete();
    }
};
