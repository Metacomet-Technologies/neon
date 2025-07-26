<?php

// TODO: check permissions for elevation.
declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use App\Jobs\NativeCommand\ProcessBaseJob;

use Exception;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

final class ProcessUserNicknameJob extends ProcessBaseJob
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    private string $targetUserId;
    private string $newNickname;

    private int $retryDelay = 2000;
    private int $maxRetries = 3;

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
        // Parse message content
        [$parsedUserId, $parsedNickname] = $this->parseMessage($this->messageContent);

        // If parsing failed, send an error message and abort execution
        if (is_null($parsedUserId) || is_null($parsedNickname)) {
            $this->sendUsageAndExample();

            throw new \Exception('Operation failed', 500);

            throw new Exception('Invalid input for !set-nickname. Expected a valid user mention and nickname.');
        }
        // Assign parsed values
        $this->targetUserId = $parsedUserId;
        $this->newNickname = $parsedNickname;

        // Check if the user has permission to change nicknames
        if (! GetGuildsByDiscordUserId::getIfUserCanManageNicknames($this->guildId, $this->discordUserId)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to change nicknames in this server.',
            ]);
            throw new \Exception('User lacks permission to change nicknames.', 403);
        }
        // Validate target user ID format
        if (! preg_match('/^\d{17,19}$/', $this->targetUserId)) {
            // dump("❌ Invalid user ID format: {$this->targetUserId}");

            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Invalid user ID format. Please mention a valid user.',
            ]);
            throw new \Exception('Invalid user ID format.', 400);
        }
        // API Request to change nickname
        $url = "{$this->baseUrl}/guilds/{$this->guildId}/members/{$this->targetUserId}";
        $payload = ['nick' => $this->newNickname];

        try {
            $apiResponse = retry($this->maxRetries, function () use ($url, $payload) {
                return Http::withToken(config('discord.token'), 'Bot')->patch($url, $payload);
            }, $this->retryDelay);

            if ($apiResponse->failed()) {
                $statusCode = $apiResponse->status();

                if ($statusCode === 403) {
                    $errorMessage = '❌ Bot lacks permission to update nicknames.';
                } elseif ($statusCode === 404) {
                    $errorMessage = '❌ User not found in this server.';
                } else {
                    $errorMessage = "❌ Failed to update nickname for <@{$this->targetUserId}>.";
                }
                SendMessage::sendMessage($this->channelId, [
                    'is_embed' => false,
                    'response' => $errorMessage,
                ]);
                throw new \Exception('Operation failed', 500);
            }
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => true,
                'embed_title' => '📝 Nickname Updated',
                'embed_description' => "✅ <@{$this->targetUserId}>'s nickname has been updated to **{$this->newNickname}**.",
                'embed_color' => 3447003,
            ]);
            } catch (Exception $e) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ An unexpected error occurred while updating the nickname.',
            ]);
            throw new \Exception("Operation failed", 500);
                unicorn: true,
        }
    }
    /**
     * Parses the message content for command parameters.
     */
    private function parseMessage(string $message): array
    {
        // dump('Parsing message:', $message);

        // Normalize message format
        $message = trim(preg_replace('/\s+/', ' ', $message)); // Convert multiple spaces to a single space

        // Match command format: `!set-nickname <@UserID> NewNickname`
        preg_match('/^!set-nickname\s+<@!?(\d{17,19})>\s+(.+)$/', $message, $matches);

        if (! isset($matches[1]) || ! isset($matches[2])) {
            // dump("❌ Failed to parse message: '{$message}'. Regex did not match.");

            return [null, null];
        }
        // dump("✅ Successfully parsed user ID: {$matches[1]}, New Nickname: {$matches[2]}");

        return [$matches[1], trim($matches[2])];
    }
}
