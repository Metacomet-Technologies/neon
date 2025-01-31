<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\SendMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessAssignRoleJob implements ShouldQueue
{
    use Queueable;

    public string $usageMessage = 'Usage: !assign-role <role-name> <@user1> <@user2> ...';
    public int $batchSize = 5; // ✅ Process users in groups of 5 to avoid rate limits
    public int $retryDelay = 2000; // ✅ 2-second delay before retrying
    public int $maxRetries = 3; // ✅ Max retries per request

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $channelId,
        public string $guildId,
        public string $messageContent,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1️⃣ Parse command arguments
        $parts = explode(' ', $this->messageContent);

        // Validate input
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

        // 3️⃣ Allow Discord to update roles before fetching
        sleep(2);

        // Fetch all roles in the guild with Laravel's retry
        $rolesUrl = config('services.discord.rest_api_url') . "/guilds/{$this->guildId}/roles";
        $rolesResponse = retry($this->maxRetries, function () use ($rolesUrl) {
            return Http::withToken(config('discord.token'), 'Bot')->get($rolesUrl);
        }, $this->retryDelay);

        if ($rolesResponse->failed()) {
            Log::error("Failed to fetch roles for guild {$this->guildId}");
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to retrieve roles from the server.',
            ]);

            return;
        }

        // 4️⃣ Find the role by name
        $roles = $rolesResponse->json();
        $role = collect($roles)->first(fn ($r) => strcasecmp($r['name'], $roleName) === 0);

        if (! $role) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Role '{$roleName}' not found.",
            ]);

            return;
        }

        $roleId = $role['id']; // Extract role ID

        // 5️⃣ Assign the role in batches (to avoid rate limits)
        $failedUsers = [];
        $successfulUsers = [];

        $chunks = array_chunk($userIds, $this->batchSize); // ✅ Split into batches of 5
        foreach ($chunks as $batchIndex => $batch) {
            foreach ($batch as $userId) {
                $assignUrl = config('services.discord.rest_api_url') . "/guilds/{$this->guildId}/members/{$userId}/roles/{$roleId}";

                // Use Laravel's retry for assigning roles
                $assignResponse = retry($this->maxRetries, function () use ($assignUrl) {
                    return Http::withToken(config('discord.token'), 'Bot')->put($assignUrl);
                }, $this->retryDelay);

                if ($assignResponse->failed()) {
                    $failedUsers[] = "<@{$userId}>";
                } else {
                    $successfulUsers[] = "<@{$userId}>";
                }
            }

            // ✅ Introduce a retry delay between batches instead of a fixed sleep
            if ($batchIndex < count($chunks) - 1) {
                retry(1, function () {
                    sleep($this->retryDelay / 1000); // Convert ms to seconds

                    return true;
                }, $this->retryDelay);
            }
        }

        // ✅ Send Result Message
        $successMessage = count($successfulUsers) > 0
            ? "✅ Assigned role '{$roleName}' to: " . implode(', ', $successfulUsers)
            : '';

        $errorMessage = count($failedUsers) > 0
            ? "❌ Failed to assign role '{$roleName}' to: " . implode(', ', $failedUsers)
            : '';

        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '🔹 Role Assignment Results',
            'embed_description' => trim($successMessage . "\n" . $errorMessage),
            'embed_color' => count($successfulUsers) > 0 ? 3066993 : 15158332, // Green if success, Red if failure
        ]);
    }
}
