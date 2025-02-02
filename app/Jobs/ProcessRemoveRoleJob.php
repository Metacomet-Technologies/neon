<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\SendMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessRemoveRoleJob implements ShouldQueue
{
    use Queueable;

    public string $usageMessage = 'Usage: !remove-role <role-name> <@user1> <@user2> ...';
    public int $batchSize = 5; // ✅ Process users in groups of 5 to avoid rate limits
    public int $delayBetweenBatches = 2; // ✅ 2-second delay between batches

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $discordUserId,
        public string $channelId,
        public string $guildId,
        public string $messageContent,
    ) {}

// TODO: Check if the user is the owner and send owner access token for elevated permissions. This whole job may need permission checks.


    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Processing remove role command: {$this->messageContent}");

        // 1️⃣ Parse command arguments
        $parts = explode(' ', $this->messageContent);

        if (count($parts) < 3) {
            SendMessage::sendMessage($this->channelId, ['is_embed' => false, 'response' => $this->usageMessage]);

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

            return;
        }

        // 4️⃣ Find the role by name
        $roles = collect($rolesResponse->json());
        $role = $roles->first(fn ($r) => strtolower($r['name']) === strtolower($roleName));

        if (! $role) {
            SendMessage::sendMessage($this->channelId, ['is_embed' => false, 'response' => "❌ Role '{$roleName}' not found."]);

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

            // ✅ Wait before sending the next batch to avoid rate limits
            if (count($chunks) > 1) {
                sleep($this->delayBetweenBatches);
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
    }
}
