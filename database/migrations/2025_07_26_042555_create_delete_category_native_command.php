<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'delete-category'], [
            'slug' => 'delete-category',
            'description' => 'Deletes a category.',
            'class' => \App\Jobs\ProcessDeleteCategoryJob::class,
            'usage' => 'Usage: !delete-category <category-id>',
            'example' => 'Example: !delete-category 123456789012345678',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'delete-category')->delete();
    }
};
