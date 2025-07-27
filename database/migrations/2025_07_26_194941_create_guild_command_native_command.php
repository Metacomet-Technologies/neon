<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'guild-command'], [
            'slug' => 'guild-command',
            'description' => 'Processes custom guild commands.',
            'class' => \App\Jobs\NativeCommand\ProcessGuildCommandJob::class,
            'usage' => 'Usage: Internal command processor',
            'example' => 'Example: Internal use only',
            'is_active' => false,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'guild-command')->delete();
    }
};
