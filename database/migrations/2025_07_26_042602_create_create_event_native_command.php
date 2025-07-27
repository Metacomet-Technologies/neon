<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'create-event'], [
            'slug' => 'create-event',
            'description' => 'Creates a scheduled event.',
            'class' => \App\Jobs\NativeCommand\ProcessNewEventJob::class,
            'usage' => 'Usage: !create-event <name> <date> <time>',
            'example' => 'Example: !create-event "Community Meeting" 2024-01-15 19:00',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'create-event')->delete();
    }
};
