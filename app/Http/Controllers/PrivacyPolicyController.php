<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

final class PrivacyPolicyController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke()
    {
        $path = 'neon/legal/privacy-policy.md';

        if (! Storage::disk('s3')->exists($path)) {
            abort(404, 'Markdown file not found');
        }

        $markdownContent = Storage::disk('s3')->get($path);

        return Inertia::render('Markdown', [
            'content' => $markdownContent,
        ]);
    }
}
