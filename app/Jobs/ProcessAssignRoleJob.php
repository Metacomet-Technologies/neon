<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessAssignRoleJob implements ShouldQueue
{
    use Queueable;

    public string $usageMessage;
    public string $exampleMessage;

    // 'slug' => 'assign-role',
    // 'description' => 'Assigns a role to one or more users.',
    // 'class' => \App\Jobs\ProcessAssignRoleJob::class,
    // 'usage' => 'Usage: !assign-role <role-name> <@user1> <@user2> ...',
    // 'example' => 'Example: !assign-role VIP 987654321098765432',
    // 'is_active' => true,

    public int $batchSize = 5; // ✅ Process users in groups of 5 to avoid rate limits
    public int $retryDelay = 2000; // ✅ 2-second delay before retrying
    public int $maxRetries = 3; // ✅ Max retries per request

    public function __construct(
        public string $discordUserId,
        public string $channelId,
        public string $guildId,
        public string $messageContent,
    ) {
        $command = DB::table('native_commands')->where('slug', 'assign-role')->first();
        $this->usageMessage = $command->usage;
        $this->exampleMessage = $command->example;
    }

    public function handle(): void
    {
        $permissionCheck = GetGuildsByDiscordUserId::getIfUserCanManageRoles($this->guildId, $this->discordUserId);
        if ($permissionCheck !== 'success') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to manage roles in this server.',
            ]);

            return;
        }

        $parts = explode(' ', $this->messageContent);
        if (count($parts) < 3) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "{$this->usageMessage}\n{$this->exampleMessage}",
            ]);

            return;
        }

        $roleName = $parts[1];
        $userMentions = array_slice($parts, 2);
        $userIds = [];

        foreach ($userMentions as $mention) {
            if (! preg_match('/^<@!?(\d+)>$/', $mention, $matches)) {
                SendMessage::sendMessage($this->channelId, [
                    'is_embed' => false,
                    'response' => "❌ Invalid user mention format: {$mention}",
                ]);

                return;
            }
            $userIds[] = $matches[1];
        }

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

        $roles = $rolesResponse->json();
        $role = collect($roles)->first(fn ($r) => strcasecmp($r['name'], $roleName) === 0);

        if (! $role) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Role '{$roleName}' not found.",
            ]);

            return;
        }

        $roleId = $role['id'];
        $failedUsers = [];
        $successfulUsers = [];

        $chunks = array_chunk($userIds, $this->batchSize);
        foreach ($chunks as $batchIndex => $batch) {
            foreach ($batch as $userId) {
                $assignUrl = config('services.discord.rest_api_url') . "/guilds/{$this->guildId}/members/{$userId}/roles/{$roleId}";
                $assignResponse = retry($this->maxRetries, function () use ($assignUrl) {
                    return Http::withToken(config('discord.token'), 'Bot')->put($assignUrl);
                }, $this->retryDelay);

                if ($assignResponse->failed()) {
                    $failedUsers[] = "<@{$userId}>";
                } else {
                    $successfulUsers[] = "<@{$userId}>";
                }
            }

            if ($batchIndex < count($chunks) - 1) {
                retry(1, function () {
                    usleep($this->retryDelay * 1000); // ✅ Laravel retry for batch delay

                    return true;
                }, $this->retryDelay);
            }
        }

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
            'embed_color' => count($successfulUsers) > 0 ? 3066993 : 15158332,
        ]);
    }
}
