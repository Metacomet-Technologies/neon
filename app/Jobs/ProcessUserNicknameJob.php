<?php

//TODO: check permissions for elevation.
declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

final class ProcessUserNicknameJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public string $usageMessage;
    public string $exampleMessage;

    private string $baseUrl;
    private string $targetUserId;
    private string $newNickname;

    private int $retryDelay = 2000;
    private int $maxRetries = 3;

    public function __construct(
        public string $discordUserId, // Command sender
        public string $channelId,     // The channel where the command was sent
        public string $guildId,       // The guild (server) ID
        public string $messageContent // The raw message content
    ) {
        // dump("Processing !set-nickname command from {$this->discordUserId} in channel {$this->channelId}");

        $command = DB::table('native_commands')->where('slug', 'set-nickname')->first();
        // dump('Command Data:', $command);

        if (! $command) {
            throw new Exception('Command configuration missing from database.');
        }

        $this->usageMessage = $command->usage;
        $this->exampleMessage = $command->example;
        $this->baseUrl = config('services.discord.rest_api_url');

        // Parse message content
        [$parsedUserId, $parsedNickname] = $this->parseMessage($this->messageContent);
        // dump('Parsed User ID:', $parsedUserId);
        // dump('Parsed Nickname:', $parsedNickname);

        // If parsing failed, send an error message and abort execution
        if (is_null($parsedUserId) || is_null($parsedNickname)) {
            // dump('❌ Invalid input detected, aborting job.');

            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "{$this->usageMessage}\n{$this->exampleMessage}",
            ]);

            throw new Exception('Invalid input for !set-nickname. Expected a valid user mention and nickname.');
        }

        // Assign parsed values
        $this->targetUserId = $parsedUserId;
        $this->newNickname = $parsedNickname;
    }

    public function handle(): void
    {
        // dump("Handling nickname update for user {$this->targetUserId} in guild {$this->guildId}");

        // Check if the user has permission to change nicknames
        if (! GetGuildsByDiscordUserId::getIfUserCanManageNicknames($this->guildId, $this->discordUserId)) {
            // dump('❌ User does not have permission to change nicknames.');

            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to change nicknames in this server.',
            ]);

            return;
        }

        // Validate target user ID format
        if (! preg_match('/^\d{17,19}$/', $this->targetUserId)) {
            // dump("❌ Invalid user ID format: {$this->targetUserId}");

            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Invalid user ID format. Please mention a valid user.',
            ]);

            return;
        }

        // API Request to change nickname
        $url = "{$this->baseUrl}/guilds/{$this->guildId}/members/{$this->targetUserId}";
        $payload = ['nick' => $this->newNickname];

        // dump('Sending API request to update nickname:', $url, $payload);

        try {
            $apiResponse = retry($this->maxRetries, function () use ($url, $payload) {
                return Http::withToken(config('discord.token'), 'Bot')->patch($url, $payload);
            }, $this->retryDelay);

            // dump('API Response Status:', $apiResponse->status());

            if ($apiResponse->failed()) {
                $statusCode = $apiResponse->status();

                if ($statusCode === 403) {
                    $errorMessage = '❌ Bot lacks permission to update nicknames.';
                } elseif ($statusCode === 404) {
                    $errorMessage = '❌ User not found in this server.';
                } else {
                    $errorMessage = "❌ Failed to update nickname for <@{$this->targetUserId}>.";
                }

                // dump("❌ API request failed. Status Code: {$statusCode}");

                SendMessage::sendMessage($this->channelId, [
                    'is_embed' => false,
                    'response' => $errorMessage,
                ]);

                return;
            }

            // Success message
            // dump("✅ Nickname updated successfully for <@{$this->targetUserId}>: {$this->newNickname}");

            SendMessage::sendMessage($this->channelId, [
                'is_embed' => true,
                'embed_title' => '📝 Nickname Updated',
                'embed_description' => "✅ <@{$this->targetUserId}>'s nickname has been updated to **{$this->newNickname}**.",
                'embed_color' => 3447003,
            ]);

        } catch (Exception $e) {
            // dump('❌ Unexpected error updating nickname:', $e->getMessage());

            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ An unexpected error occurred while updating the nickname.',
            ]);
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
