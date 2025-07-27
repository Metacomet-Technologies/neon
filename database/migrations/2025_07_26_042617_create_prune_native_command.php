<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'prune'], [
            'slug' => 'prune',
            'description' => 'Prunes inactive members from the server.',
            'class' => \App\Jobs\NativeCommand\ProcessPruneInactiveMembersJob::class,
            'usage' => 'Usage: !prune <days-inactive>',
            'example' => 'Example: !prune 30',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'prune')->delete();
    }
};
