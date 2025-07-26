<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use Exception;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\DiscordApiService;
use Illuminate\Support\Facades\Log;

final class ProcessDeleteEventJob extends ProcessBaseJob
{
    use Queueable;

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

    /**
     * Execute the job.
     */
    protected function executeCommand(): void
    {
        // Ensure the user has permission to manage events
        $permissionCheck = GetGuildsByDiscordUserId::getIfUserCanCreateEvents($this->guildId, $this->discordUserId);

        if ($permissionCheck !== 'success') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to delete events in this server.',
            ]);
            throw new Exception('User does not have permission to delete events.', 403);
        }

        // Parse the command message
        $eventId = $this->parseMessage($this->messageContent);

        if (! $eventId) {
            $this->sendUsageAndExample();

            throw new Exception('No user ID provided.', 400);
        }

        // Make the delete request
        $discordService = app(DiscordApiService::class);
        
        try {
            $deleteResponse = $discordService->delete("/guilds/{$this->guildId}/scheduled-events/{$eventId}");

            if ($deleteResponse->failed()) {
                Log::error("Failed to delete event '{$eventId}' in guild {$this->guildId}", [
                    'response' => $deleteResponse->json(),
                ]);

                SendMessage::sendMessage($this->channelId, [
                    'is_embed' => false,
                    'response' => "❌ Failed to delete event (ID: `{$eventId}`).",
                ]);
                throw new Exception('Failed to delete event.', 500);
            }
        } catch (Exception $e) {
            Log::error("Exception while deleting event '{$eventId}' in guild {$this->guildId}", ['error' => $e->getMessage()]);

            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Failed to delete event (ID: `{$eventId}`).",
            ]);
            throw new Exception('Failed to delete event: ' . $e->getMessage(), 500);
        }

        // ✅ Success! Send confirmation message
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '✅ Event Deleted!',
            'embed_description' => "**Event ID:** `{$eventId}` has been successfully removed.",
            'embed_color' => 15158332, // Red embed
        ]);
    }

    /**
     * Parses the message content for extracting the event ID.
     */
    private function parseMessage(string $message): ?string
    {
        // Remove invisible characters (zero-width spaces, control characters)
        $cleanedMessage = preg_replace('/[\p{Cf}]/u', '', $message); // Removes control characters
        $cleanedMessage = trim(preg_replace('/\s+/', ' ', $cleanedMessage)); // Normalize spaces

        // Use regex to extract the event ID
        preg_match('/^!delete-event\s+(\d{17,19})$/iu', $cleanedMessage, $matches);

        return $matches[1] ?? null;
    }
}
