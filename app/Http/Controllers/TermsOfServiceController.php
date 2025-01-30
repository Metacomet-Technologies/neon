<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

final class TermsOfServiceController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke()
    {
        $path = 'neon/legal/terms-of-service.md';

        if (! Storage::disk('s3')->exists($path)) {
            abort(404, 'Markdown file not found');
        }

        $markdownContent = Storage::disk('s3')->get($path);

        Inertia::render('Markdown', [
            'content' => $markdownContent,
        ]);
    }
}
