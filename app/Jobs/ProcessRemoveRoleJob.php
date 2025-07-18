<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use App\Jobs\NativeCommand\ProcessBaseJob;
use App\Models\NativeCommandRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessRemoveRoleJob extends ProcessBaseJob implements ShouldQueue
{
    use Queueable;
    public int $batchSize = 5; // ✅ Process users in groups of 5 to avoid rate limits
    public int $delayBetweenBatches = 2; // ✅ 2-second delay between batches

    public function __construct(public NativeCommandRequest $nativeCommandRequest)
    {
        parent::__construct($nativeCommandRequest);
    }

    // TODO: Check if the user is the owner and send owner access token for elevated permissions. This whole job may need permission checks.

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Ensure the user has permission to manage channels
        $permissionCheck = GetGuildsByDiscordUserId::getIfUserCanManageRoles($this->guildId, $this->discordUserId);

        if ($permissionCheck !== 'success') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to manage roles in this server.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'unauthorized',
                message: 'User does not have permission to manage roles in this server.',
                statusCode: 403,
            );

            return;
        }

        // 1️⃣ Parse command arguments
        $parts = explode(' ', $this->messageContent);

        if (count($parts) < 3) {
            $this->sendUsageAndExample();

            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'No user ID provided.',
                statusCode: 400,
            );

            return;
        }

        // 2️⃣ Extract role name and user mentions
        $roleName = $parts[1];
        $userMentions = array_slice($parts, 2);
        $userIds = [];

        // Validate user mentions
        foreach ($userMentions as $mention) {
            if (! preg_match('/^<@!?(\d+)>$/', $mention, $matches)) {
                SendMessage::sendMessage($this->channelId, [
                    'is_embed' => false,
                    'response' => "❌ Invalid user mention format: {$mention}",
                ]);
                $this->updateNativeCommandRequestFailed(
                    status: 'failed',
                    message: 'Invalid user mention format.',
                    statusCode: 400,
                );

                return;
            }
            $userIds[] = $matches[1]; // Extract user ID from mention
        }

        // 3️⃣ Fetch all roles in the guild (retry up to 3 times)
        $rolesUrl = config('services.discord.rest_api_url') . "/guilds/{$this->guildId}/roles";
        $rolesResponse = retry(3, function () use ($rolesUrl) {
            return Http::withToken(config('discord.token'), 'Bot')->get($rolesUrl);
        }, 200);

        if ($rolesResponse->failed()) {
            Log::error("Failed to fetch roles for guild {$this->guildId}", ['response' => $rolesResponse->json()]);
            SendMessage::sendMessage($this->channelId, ['is_embed' => false, 'response' => '❌ Failed to retrieve roles from the server.']);
            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'Failed to retrieve roles from the server.',
                statusCode: 500,
            );

            return;
        }

        // 4️⃣ Find the role by name
        $roles = collect($rolesResponse->json());
        $role = $roles->first(fn ($r) => strtolower($r['name']) === strtolower($roleName));

        if (! $role) {
            SendMessage::sendMessage($this->channelId, ['is_embed' => false, 'response' => "❌ Role '{$roleName}' not found."]);
            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'Role not found.',
                statusCode: 404,
            );

            return;
        }

        $roleId = $role['id'];

        // 5️⃣ Remove the role from users in batches (retry each API call up to 3 times)
        $failedUsers = [];
        $successfulUsers = [];

        $chunks = array_chunk($userIds, $this->batchSize); // ✅ Process in small batches to avoid rate limits
        foreach ($chunks as $batch) {
            foreach ($batch as $userId) {
                $removeUrl = config('services.discord.rest_api_url') . "/guilds/{$this->guildId}/members/{$userId}/roles/{$roleId}";

                $removeResponse = retry(3, function () use ($removeUrl) {
                    return Http::withToken(config('discord.token'), 'Bot')->delete($removeUrl);
                }, 200);

                if ($removeResponse->failed()) {
                    $failedUsers[] = "<@{$userId}>";
                } else {
                    $successfulUsers[] = "<@{$userId}>";
                }
            }

            // ✅ Use Laravel's backoff instead of sleep
            if (count($chunks) > 1) {
                retry(3, function () {
                    // This is a placeholder to trigger the backoff mechanism
                    return true;
                }, $this->delayBetweenBatches * 1000); // Convert seconds to milliseconds
            }
        }

        // ✅ Send Result Message
        $successMessage = count($successfulUsers) > 0
            ? "✅ Removed role '{$roleName}' from: " . implode(', ', $successfulUsers)
            : '';

        $errorMessage = count($failedUsers) > 0
            ? "❌ Failed to remove role '{$roleName}' from: " . implode(', ', $failedUsers)
            : '';

        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '🔹 Role Removal Results',
            'embed_description' => trim($successMessage . "\n" . $errorMessage),
            'embed_color' => count($successfulUsers) > 0 ? 3066993 : 15158332, // Green if success, Red if failure
        ]);
        $this->updateNativeCommandRequestComplete();
    }
}
