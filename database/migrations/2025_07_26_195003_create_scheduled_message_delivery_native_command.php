<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'scheduled-message-delivery'], [
            'slug' => 'scheduled-message-delivery',
            'description' => 'Delivers scheduled messages at specified times.',
            'class' => \App\Jobs\NativeCommand\ProcessScheduledMessageDeliveryJob::class,
            'usage' => 'Usage: Internal command processor',
            'example' => 'Example: Internal use only',
            'is_active' => false,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'scheduled-message-delivery')->delete();
    }
};
