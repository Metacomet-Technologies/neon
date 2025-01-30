<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

final class ServerController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return Inertia::render('Servers/Index', [
            'guilds' => $request->user()->guilds,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $serverId)
    {
        $guilds = $request->user()->guilds;

        if (! in_array($serverId, array_column($guilds, 'id'))) {
            abort(403);
        }

        $request->user()->current_server_id = $serverId;
        $request->user()->save();

        return Inertia::render('Servers/Show');
    }
}
