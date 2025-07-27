<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'purge'], [
            'slug' => 'purge',
            'description' => 'Purges messages from the channel.',
            'class' => \App\Jobs\NativeCommand\ProcessPurgeMessagesJob::class,
            'usage' => 'Usage: !purge <number-of-messages>',
            'example' => 'Example: !purge 50',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'purge')->delete();
    }
};
