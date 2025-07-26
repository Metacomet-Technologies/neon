<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'pin'], [
            'slug' => 'pin',
            'description' => 'Pins a message in the channel.',
            'class' => \App\Jobs\ProcessPinMessageJob::class,
            'usage' => 'Usage: !pin <message-id>',
            'example' => 'Example: !pin 123456789012345678',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'pin')->delete();
    }
};
