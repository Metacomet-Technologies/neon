<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'delete-event'], [
            'slug' => 'delete-event',
            'description' => 'Deletes a scheduled event.',
            'class' => \App\Jobs\ProcessDeleteEventJob::class,
            'usage' => 'Usage: !delete-event <event-id>',
            'example' => 'Example: !delete-event 123456789012345678',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'delete-event')->delete();
    }
};
