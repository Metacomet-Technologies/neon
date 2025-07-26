<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'unvanish'], [
            'slug' => 'unvanish',
            'description' => 'Makes a vanished channel visible again to members.',
            'class' => \App\Jobs\NativeCommand\ProcessUnvanishChannelJob::class,
            'usage' => 'Usage: !unvanish <channel-id>',
            'example' => 'Example: !unvanish 123456789012345678',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'unvanish')->delete();
    }
};
