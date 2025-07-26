<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'set-inactive'], [
            'slug' => 'set-inactive',
            'description' => 'Sets a member as inactive.',
            'class' => \App\Jobs\NativeCommand\ProcessSetInactiveJob::class,
            'usage' => 'Usage: !set-inactive <user-id>',
            'example' => 'Example: !set-inactive 123456789012345678',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'set-inactive')->delete();
    }
};
