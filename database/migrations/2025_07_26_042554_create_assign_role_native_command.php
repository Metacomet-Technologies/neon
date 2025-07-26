<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'assign-role'], [
            'slug' => 'assign-role',
            'description' => 'Assigns a role to one or more users.',
            'class' => \App\Jobs\ProcessAssignRoleJob::class,
            'usage' => 'Usage: !assign-role <role-name> <@user1> <@user2> ...',
            'example' => 'Example: !assign-role VIP 987654321098765432',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'assign-role')->delete();
    }
};
