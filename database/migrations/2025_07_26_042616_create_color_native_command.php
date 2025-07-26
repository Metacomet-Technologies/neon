<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'color'], [
            'slug' => 'color',
            'description' => 'Changes the color of a role.',
            'class' => \App\Jobs\ProcessChangeRoleColorJob::class,
            'usage' => 'Usage: !color <role-name> <hex-color>',
            'example' => 'Example: !color VIP #ff0000',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'color')->delete();
    }
};
