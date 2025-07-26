<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'remove-role'], [
            'slug' => 'remove-role',
            'description' => 'Removes a role from one or more users.',
            'class' => \App\Jobs\ProcessRemoveRoleJob::class,
            'usage' => 'Usage: !remove-role <role-name> <@user1> <@user2> ...',
            'example' => 'Example: !remove-role VIP 123456789012345678',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'remove-role')->delete();
    }
};
