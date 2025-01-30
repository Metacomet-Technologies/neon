<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\NeonCommandRequest;
use App\Models\NeonCommand;
use Illuminate\Http\Request;
use Inertia\Inertia;

final class CommandController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, string $serverId): \Inertia\Response
    {
        /** @var array $guilds */
        $guilds = $request->user()->guilds;
        $guildIds = array_column($guilds, 'id');

        if (! in_array($serverId, $guildIds)) {
            abort(403, 'You are not authorized to view this page.');
        }

        $page = request()->input('page', 1);
        $perPage = request()->input('perPage', 10);

        return Inertia::render('Command/Index', [
            'commands' => NeonCommand::query()
                ->with(['createdByUser', 'updatedByUser'])
                ->where('guild_id', $serverId)
                ->latest()
                ->paginate(page: $page, perPage: $perPage),
            'serverId' => $serverId,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request, string $serverId): \Inertia\Response
    {
        /** @var array $guilds */
        $guilds = $request->user()->guilds;
        $guildIds = array_column($guilds, 'id');

        if (! in_array($serverId, $guildIds)) {
            abort(403, 'You are not authorized to view this page.');
        }

        return Inertia::render('Command/Create', [
            'serverId' => $serverId,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(NeonCommandRequest $request, string $serverId): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        /** @var array $guilds */
        $guilds = $user->guilds;
        $guildIds = array_column($guilds, 'id');

        if (! in_array($serverId, $guildIds)) {
            abort(403, 'You are not authorized to view this page.');
        }

        $now = now();

        $newCommand = new NeonCommand;
        $newCommand->command = $request->input('command');
        $newCommand->description = $request->input('description', null);
        $newCommand->response = $request->input('response');
        $newCommand->is_enabled = $request->input('is_enabled');
        $newCommand->is_public = $request->input('is_public');
        $newCommand->guild_id = $serverId;
        $newCommand->created_by = $user->id;
        $newCommand->updated_by = $user->id;
        $newCommand->created_at = $now;
        $newCommand->updated_at = $now;
        $newCommand->save();

        return redirect()
            ->route('server.command.index', $serverId)
            ->with(['type' => 'success', 'message' => 'Command created successfully.']);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, string $serverId, NeonCommand $command): \Inertia\Response
    {
        /** @var array $guilds */
        $guilds = $request->user()->guilds;
        $guildIds = array_column($guilds, 'id');

        if (! in_array($serverId, $guildIds)) {
            abort(403, 'You are not authorized to view this page.');
        }

        return Inertia::render('Command/Edit', [
            'serverId' => $serverId,
            'command' => $command,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(NeonCommandRequest $request, string $serverId, NeonCommand $command): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        /** @var array $guilds */
        $guilds = $user->guilds;
        $guildIds = array_column($guilds, 'id');

        if (! in_array($serverId, $guildIds)) {
            abort(403, 'You are not authorized to view this page.');
        }

        $now = now();
        $now = now();
        $command->command = $request->input('command');
        $command->description = $request->input('description', null);
        $command->response = $request->input('response');
        $command->is_enabled = $request->input('is_enabled');
        $command->is_public = $request->input('is_public');
        $command->updated_by = $user->id;
        $command->updated_at = $now;
        $command->save();

        return redirect()
            ->route('server.command.index', $serverId)
            ->with(['type' => 'success', 'message' => 'Command updated successfully.']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $serverId, NeonCommand $command): \Illuminate\Http\RedirectResponse
    {
        /** @var array $guilds */
        $guilds = $request->user()->guilds;
        $guildIds = array_column($guilds, 'id');

        if (! in_array($serverId, $guildIds)) {
            abort(403, 'You are not authorized to view this page.');
        }

        $command->delete();

        return redirect()
            ->route('server.command.index', $serverId)
            ->with(['type' => 'success', 'message' => 'Command deleted successfully.']);
    }
}
