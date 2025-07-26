<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'ban'], [
            'slug' => 'ban',
            'description' => 'Bans a user from the server.',
            'class' => \App\Jobs\NativeCommand\ProcessBanUserJob::class,
            'usage' => 'Usage: !ban <user-id>',
            'example' => 'Example: !ban 123456789012345678',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'ban')->delete();
    }
};
