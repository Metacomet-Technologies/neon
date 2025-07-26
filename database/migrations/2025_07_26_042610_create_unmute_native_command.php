<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'unmute'], [
            'slug' => 'unmute',
            'description' => 'Unmutes a user in voice chat.',
            'class' => \App\Jobs\ProcessUnmuteUserJob::class,
            'usage' => 'Usage: !unmute <user-id>',
            'example' => 'Example: !unmute 123456789012345678',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'unmute')->delete();
    }
};
