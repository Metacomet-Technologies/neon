<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\WelcomeSetting;
use Illuminate\Http\Request;

class ToggleWelcomeSettingActiveController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, WelcomeSetting $welcomeSetting)
    {
        $welcomeSetting->is_active = $request->input('is_active');
        $welcomeSetting->save();

        return response()->json($welcomeSetting);
    }
}
