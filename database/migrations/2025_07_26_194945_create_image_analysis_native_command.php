<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'image-analysis'], [
            'slug' => 'image-analysis',
            'description' => 'Analyzes images using AI.',
            'class' => \App\Jobs\NativeCommand\ProcessImageAnalysisJob::class,
            'usage' => 'Usage: Upload an image with a question',
            'example' => 'Example: Upload image and ask "What is in this image?"',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'image-analysis')->delete();
    }
};
