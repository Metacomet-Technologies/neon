<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\NeonCommandRequest;
use App\Models\NeonCommand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CommandController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, string $serverId): Response
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

        $page = $request->input('page', 1);
        $perPage = $request->input('perPage', 10);

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
    public function create(Request $request, string $serverId): Response
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
    public function store(NeonCommandRequest $request, string $serverId): RedirectResponse
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

        NeonCommand::create([
            'command' => $request->validated('command'),
            'response' => $request->validated('response'),
            'description' => $request->validated('description'),
            'guild_id' => $serverId,
            'is_enabled' => $request->validated('is_enabled'),
            'is_public' => $request->validated('is_public'),
            'is_embed' => $request->validated('is_embed'),
            'embed_title' => $request->validated('embed_title'),
            'embed_description' => $request->validated('embed_description'),
            'embed_color' => $request->validated('embed_color'),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        return redirect()
            ->route('server.command.index', $serverId)
            ->with('success', 'Command created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, string $serverId, NeonCommand $command): Response
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
    public function update(NeonCommandRequest $request, string $serverId, NeonCommand $command): RedirectResponse
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

        $command->update([
            'command' => $request->validated('command'),
            'response' => $request->validated('response'),
            'description' => $request->validated('description'),
            'is_enabled' => $request->validated('is_enabled'),
            'is_public' => $request->validated('is_public'),
            'is_embed' => $request->validated('is_embed'),
            'embed_title' => $request->validated('embed_title'),
            'embed_description' => $request->validated('embed_description'),
            'embed_color' => $request->validated('embed_color'),
            'updated_by' => $user->id,
        ]);

        return redirect()
            ->route('server.command.index', $serverId)
            ->with('success', 'Command updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $serverId, NeonCommand $command): RedirectResponse
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
            ->with('success', 'Command deleted successfully.');
    }
}
