<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\DiscordPermissionEnum;
use App\Helpers\Discord\GetGuilds;
use Illuminate\Http\Request;
use Inertia\Inertia;

final class ServerController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $permission = DiscordPermissionEnum::ADMINISTRATOR;
        $guilds = (new GetGuilds($request->user()))
            ->getGuildsWhereUserHasPermission($permission);

        return Inertia::render('Servers/Index', [
            'guilds' => $guilds,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $serverId)
    {
        $request->user()->current_server_id = $serverId;
        $request->user()->save();

        return Inertia::render('Servers/Show');
    }
}
