<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'unpin'], [
            'slug' => 'unpin',
            'description' => 'Unpins a message in the channel.',
            'class' => \App\Jobs\ProcessUnpinMessageJob::class,
            'usage' => 'Usage: !unpin <message-id>',
            'example' => 'Example: !unpin 123456789012345678',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'unpin')->delete();
    }
};
