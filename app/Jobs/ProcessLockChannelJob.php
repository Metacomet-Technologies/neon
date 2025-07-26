<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\NativeCommand\ProcessBaseJob;
use App\Services\DiscordParserService;
use Exception;
use Illuminate\Support\Facades\Http;

final class ProcessLockChannelJob extends ProcessBaseJob
{
    public function __construct(
        string $discordUserId,
        string $channelId,
        string $guildId,
        string $messageContent,
        array $command,
        string $commandSlug,
        array $parameters = []
    ) {
        parent::__construct($discordUserId, $channelId, $guildId, $messageContent, $command, $commandSlug, $parameters);
    }

    protected function executeCommand(): void
    {
        // 1. Check permissions
        $this->requireChannelPermission();

        // 2. Parse channel edit command
        [$channelId, $newValue] = DiscordParserService::parseChannelEditCommand($this->messageContent, 'lock-channel');

        if (! $channelId || ! $newValue) {
            $this->sendUsageAndExample();
            throw new Exception('Missing required parameters.', 400);
        }

        $this->validateChannelId($channelId);

        // 3. Validate boolean input
        $lockStatus = $this->validateBoolean($newValue, 'Lock status');

        // 4. Get guild roles and update permissions
        $roles = $this->discord->getGuildRoles($this->guildId);
        $failedRoles = [];

        foreach ($roles as $role) {
            $roleId = $role['id'];
            $permissionsUrl = "{$this->baseUrl}/channels/{$channelId}/permissions/{$roleId}";

            $payload = [
                'deny' => $lockStatus ? (1 << 11) : 0, // Deny or allow SEND_MESSAGES
                'type' => 0, // Role
            ];

            $response = retry(3, function () use ($permissionsUrl, $payload) {
                return Http::withToken(config('discord.token'), 'Bot')->put($permissionsUrl, $payload);
            }, 2000);

            if ($response->failed()) {
                $failedRoles[] = $role['name'];
            }
        }

        // 5. Send confirmation
        if (! empty($failedRoles)) {
            $action = $lockStatus ? 'Lock' : 'Unlock';
            $this->sendBatchResults(
                $action . ' Channel',
                [],
                $failedRoles,
                'role'
            );
        } else {
            $action = $lockStatus ? 'locked' : 'unlocked';
            $emoji = $lockStatus ? '🔒' : '🔓';
            $this->sendChannelActionConfirmation($action, $channelId, "{$emoji} Channel access updated");
        }
    }
}
