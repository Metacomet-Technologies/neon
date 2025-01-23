<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use Illuminate\Support\Facades\Auth;

final class LogoutController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke()
    {
        Auth::logout();

        return redirect('/');
    }
}
