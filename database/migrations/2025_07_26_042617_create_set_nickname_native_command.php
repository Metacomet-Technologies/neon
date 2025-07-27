<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'set-nickname'], [
            'slug' => 'set-nickname',
            'description' => 'Sets a nickname for a member.',
            'class' => \App\Jobs\NativeCommand\ProcessUserNicknameJob::class,
            'usage' => 'Usage: !set-nickname <user-id> <new-nickname>',
            'example' => 'Example: !set-nickname 123456789012345678 "Cool Member"',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'set-nickname')->delete();
    }
};
