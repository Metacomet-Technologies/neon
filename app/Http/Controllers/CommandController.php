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
        $user = $request->user();

        if (! $user) {
            abort(403, 'You are not authorized to view this page.');
        }

        /** @var list<array<string, mixed>> $guilds */
        $guilds = $user->guilds;
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
        $user = $request->user();

        if (! $user) {
            abort(403, 'You are not authorized to view this page.');
        }

        /** @var list<array<string, mixed>> $guilds */
        $guilds = $user->guilds;
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

        if (! $user) {
            abort(403, 'You are not authorized to view this page.');
        }

        /** @var list<array<string, mixed>> $guilds */
        $guilds = $user->guilds;
        $guildIds = array_column($guilds, 'id');

        if (! in_array($serverId, $guildIds)) {
            abort(403, 'You are not authorized to view this page.');
        }

        $now = now();

        $newCommand = new NeonCommand;
        $newCommand->command = $request->input('command');
        $newCommand->response = $request->input('response');
        $newCommand->description = $request->input('description', null);
        $newCommand->guild_id = $serverId;
        $newCommand->is_enabled = $request->input('is_enabled');
        $newCommand->is_public = $request->input('is_public');
        $newCommand->is_embed = $request->input('is_embed');
        $newCommand->embed_title = $request->input('embed_title', null);
        $newCommand->embed_description = $request->input('embed_description', null);
        $newCommand->embed_color = $request->input('embed_color', null);
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
        $user = $request->user();

        if (! $user) {
            abort(403, 'You are not authorized to view this page.');
        }

        /** @var list<array<string, mixed>> $guilds */
        $guilds = $user->guilds;
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

        if (! $user) {
            abort(403, 'You are not authorized to view this page.');
        }

        /** @var list<array<string, mixed>> $guilds */
        $guilds = $user->guilds;
        $guildIds = array_column($guilds, 'id');

        if (! in_array($serverId, $guildIds)) {
            abort(403, 'You are not authorized to view this page.');
        }

        $now = now();
        $now = now();
        $command->command = $request->input('command');
        $command->response = $request->input('response');
        $command->description = $request->input('description', null);
        $command->is_enabled = $request->input('is_enabled');
        $command->is_public = $request->input('is_public');
        $command->is_embed = $request->input('is_embed');
        $command->embed_title = $request->input('embed_title', null);
        $command->embed_description = $request->input('embed_description', null);
        $command->embed_color = $request->input('embed_color', null);
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
        $user = $request->user();

        if (! $user) {
            abort(403, 'You are not authorized to view this page.');
        }

        /** @var list<array<string, mixed>> $guilds */
        $guilds = $user->guilds;
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
