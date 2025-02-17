<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\UserIntegration;
use Illuminate\Http\Request;
use Inertia\Inertia;

final class ProfileController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $integrations = UserIntegration::where('user_id', $request->user()->id)->get();

        return Inertia::render('Profile', [
            'integrations' => $integrations,
        ]);
    }
}
