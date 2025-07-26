<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'support'], [
            'slug' => 'support',
            'description' => 'Shows support information and contact details.',
            'class' => \App\Jobs\ProcessShowSupportJob::class,
            'usage' => 'Usage: !support',
            'example' => 'Example: !support',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'support')->delete();
    }
};
