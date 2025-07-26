<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'delete-role'], [
            'slug' => 'delete-role',
            'description' => 'Deletes a role.',
            'class' => \App\Jobs\ProcessDeleteRoleJob::class,
            'usage' => 'Usage: !delete-role <role-name>',
            'example' => 'Example: !delete-role VIP',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'delete-role')->delete();
    }
};
