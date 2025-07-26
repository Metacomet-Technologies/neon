<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'new-category'], [
            'slug' => 'new-category',
            'description' => 'Creates a new category.',
            'class' => \App\Jobs\NativeCommand\ProcessNewCategoryJob::class,
            'usage' => 'Usage: !new-category <category-name>',
            'example' => 'Example: !new-category "General Channels"',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'new-category')->delete();
    }
};
