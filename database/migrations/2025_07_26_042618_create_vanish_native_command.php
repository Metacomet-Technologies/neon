<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'vanish'], [
            'slug' => 'vanish',
            'description' => 'Makes a channel temporarily invisible to members.',
            'class' => \App\Jobs\NativeCommand\ProcessVanishChannelJob::class,
            'usage' => 'Usage: !vanish <channel-id> [duration]',
            'example' => 'Example: !vanish 123456789012345678 1h',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'vanish')->delete();
    }
};
