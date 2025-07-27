<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'help'], [
            'slug' => 'help',
            'description' => 'Shows help information for bot commands.',
            'class' => \App\Jobs\NativeCommand\ProcessHelpCommandJob::class,
            'usage' => 'Usage: !help [command-name]',
            'example' => 'Example: !help ban',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'help')->delete();
    }
};
