<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'display-boost'], [
            'slug' => 'display-boost',
            'description' => 'Displays Nitro boost bar status.',
            'class' => \App\Jobs\NativeCommand\ProcessDisplayBoostJob::class,
            'usage' => 'Usage: !display-boost <true|false>',
            'example' => 'Example: !display-boost true',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'display-boost')->delete();
    }
};
