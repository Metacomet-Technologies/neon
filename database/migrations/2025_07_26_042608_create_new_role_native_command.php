<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'new-role'], [
            'slug' => 'new-role',
            'description' => 'Creates a new role.',
            'class' => \App\Jobs\ProcessNewRoleJob::class,
            'usage' => 'Usage: !new-role <role-name> [color] [permissions]',
            'example' => 'Example: !new-role "VIP Member" #ff0000',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'new-role')->delete();
    }
};
