<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'disconnect'], [
            'slug' => 'disconnect',
            'description' => 'Disconnects a user from voice chat.',
            'class' => \App\Jobs\NativeCommand\ProcessDisconnectUserJob::class,
            'usage' => 'Usage: !disconnect <user-id>',
            'example' => 'Example: !disconnect 123456789012345678',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'disconnect')->delete();
    }
};
