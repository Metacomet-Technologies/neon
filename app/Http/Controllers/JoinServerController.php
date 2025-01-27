<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

final class JoinServerController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): \Symfony\Component\HttpFoundation\Response|\Symfony\Component\HttpFoundation\RedirectResponse
    {
        $url = config('services.discord.join_server_url');

        if ($request->header('X-Inertia')) {
            return Inertia::location($url);
        }

        return redirect($url);
    }
}
