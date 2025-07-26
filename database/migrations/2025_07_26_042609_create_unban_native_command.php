<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'unban'], [
            'slug' => 'unban',
            'description' => 'Unbans a user from the server.',
            'class' => \App\Jobs\ProcessUnbanUserJob::class,
            'usage' => 'Usage: !unban <user-id>',
            'example' => 'Example: !unban 123456789012345678',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'unban')->delete();
    }
};
