<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use DateTime;
use DateTimeZone;
use Exception;
use App\Services\DiscordApiService;

final class ProcessNewEventJob extends ProcessBaseJob
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

    /**
     * Execute the job.
     */
    protected function executeCommand(): void
    {
        // 1️⃣ Check if user is an admin
        $adminCheck = GetGuildsByDiscordUserId::getIfUserCanCreateEvents($this->guildId, $this->discordUserId);
        if ($adminCheck === 'failed') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You are not allowed to create events.',
            ]);
            throw new Exception('User does not have permission to create events.', 403);
        }
        // 2️⃣ Parse command input
        $parts = explode('|', $this->messageContent);
        if (count($parts) < 6) {
            $this->sendUsageAndExample();

            throw new Exception('No user ID provided.', 400);
        }
        $eventTopic = trim(str_replace('!create-event', '', $parts[0]));
        $eventTopic = trim($eventTopic, '"'); // Remove extra quotes
        $startDate = trim($parts[1]);
        $startTime = trim($parts[2]);
        $eventFrequency = trim($parts[3]);
        $location = trim($parts[4]);
        $description = trim($parts[5], ' "');
        $coverImage = $parts[6] ?? null;

        // 3️⃣ Fetch all channels in the guild to check if location is a voice channel
        $discordService = app(DiscordApiService::class);
        
        try {
            $channelsResponse = $discordService->get("/guilds/{$this->guildId}/channels");
            if ($channelsResponse->failed()) {
                throw new Exception('Failed to fetch channels from the server.');
            }
            $channels = $channelsResponse->json() ?? [];
        } catch (Exception $e) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to retrieve channels from the server.',
            ]);
            throw new Exception('Failed to fetch channels from the server.', 500);
        }

        // 4️⃣ Find the channel ID if it's a voice channel
        $channelId = null;
        $isVoiceChannel = false;
        foreach ($channels as $channel) {
            if (strtolower($channel['name']) === strtolower($location) && $channel['type'] === 2) { // Type 2 = Voice Channel
                $channelId = $channel['id'];
                $isVoiceChannel = true;
                break;
            }
        }
        // 5️⃣ Determine event type (Voice Channel = 2, External/Text = 3)
        $entityType = $isVoiceChannel ? 2 : 3;

        // 6️⃣ Determine event end time (Default: 1 hour duration)
        $eventStart = new DateTime("{$startDate} {$startTime}", new DateTimeZone('UTC'));
        $eventEnd = clone $eventStart;
        $eventEnd->modify('+1 hour');

        // 7️⃣ Prepare event payload
        $eventData = [
            'name' => $eventTopic,
            'scheduled_start_time' => $eventStart->format('Y-m-d\TH:i:s\Z'),
            'scheduled_end_time' => $eventEnd->format('Y-m-d\TH:i:s\Z'),
            'description' => $description,
            'entity_type' => $entityType,
            'privacy_level' => 2,
        ];

        // 8️⃣ If it's a voice channel, use `channel_id`; otherwise, use `entity_metadata`
        if ($isVoiceChannel) {
            $eventData['channel_id'] = $channelId;
        } else {
            $eventData['entity_metadata'] = ['location' => $location];
        }
        // 9️⃣ Send API request to create the event
        try {
            $apiResponse = $discordService->post("/guilds/{$this->guildId}/scheduled-events", $eventData);

            // 🔟 Check for API errors
            if ($apiResponse->failed()) {
                SendMessage::sendMessage($this->channelId, [
                    'is_embed' => false,
                    'response' => '❌ Failed to create event.',
                ]);
                throw new Exception('Failed to create event. ' . json_encode($apiResponse->json()), 500);
            }
        } catch (Exception $e) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to create event.',
            ]);
            throw new Exception('Failed to create event: ' . $e->getMessage(), 500);
        }
        // Extract Event ID from response
        $responseData = $apiResponse->json();
        $eventId = $responseData['id'] ?? 'Unknown';

        // ✅ Send an Embedded Confirmation Message with Event ID
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '🎉 Event Created!',
            'embed_description' => "**Event:** {$eventTopic}\n**Start:** {$startDate} at {$startTime} UTC\n**Location:** " . ($isVoiceChannel ? '🔊 Voice Channel' : '🌍 External/Text Channel') . "\n**Event ID:** `{$eventId}`",
        ]);
    }
}
