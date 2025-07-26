<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'kick'], [
            'slug' => 'kick',
            'description' => 'Kicks a user from the server.',
            'class' => \App\Jobs\ProcessKickUserJob::class,
            'usage' => 'Usage: !kick <user-id> [reason]',
            'example' => 'Example: !kick 123456789012345678 "Violation of server rules"',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'kick')->delete();
    }
};
