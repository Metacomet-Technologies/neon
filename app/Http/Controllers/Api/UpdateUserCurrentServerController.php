<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;

final class UpdateUserCurrentServerController
{
    public function __invoke(Request $request, User $user): \Illuminate\Http\JsonResponse
    {
        $user->current_server_id = $request->input('server_id');
        $user->save();

        return response()->json($user);
    }
}
