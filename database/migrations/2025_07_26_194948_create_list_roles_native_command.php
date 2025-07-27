<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'list-roles'], [
            'slug' => 'list-roles',
            'description' => 'Lists all roles in the server.',
            'class' => \App\Jobs\NativeCommand\ProcessListRolesJob::class,
            'usage' => 'Usage: !list-roles',
            'example' => 'Example: !list-roles',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'list-roles')->delete();
    }
};
