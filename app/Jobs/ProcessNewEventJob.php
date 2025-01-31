<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use DateTime;
use DateTimeZone;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

final class ProcessNewEventJob implements ShouldQueue
{
    use Queueable;

    public string $baseUrl;

    public string $usageMessage = 'Usage: !create-event <event-topic> | <start-date> | <start-time> | <event-frequency> | <location> | <description> | [cover-image-url]';

    public string $exampleMessage = 'Example: !create-event "Weekly Meeting" | 2025-02-10 | 14:00 | weekly | #general | "Join us for our weekly team meeting" | https://example.com/cover.jpg';

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $discordUserId,
        public string $channelId,
        public string $guildId,
        public string $message
    ) {
        $this->baseUrl = config('services.discord.rest_api_url');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1️⃣ Check if user is an admin
        $adminCheck = GetGuildsByDiscordUserId::getIfUserCanManageChannels($this->guildId, $this->discordUserId);
        if ($adminCheck === 'failed') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You are not allowed to create events.',
            ]);

            return;
        }

        // 2️⃣ Parse command input
        $parts = explode('|', $this->message);
        if (count($parts) < 6) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '⚠️ Invalid event format. Use: !create-event "Title" | Date | Time | Frequency | Location | Description',
            ]);

            return;
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
        $guildChannelsUrl = config('services.discord.rest_api_url') . "/guilds/{$this->guildId}/channels";
        $channelsResponse = Http::withToken(config('discord.token'), 'Bot')->get($guildChannelsUrl);
        $channels = $channelsResponse->json() ?? [];

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
        $url = config('services.discord.rest_api_url') . "/guilds/{$this->guildId}/scheduled-events";
        $eventDataJson = json_encode($eventData);

        if ($eventDataJson === false) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to encode event data.',
            ]);
            throw new Exception('Failed to encode event data.');
        }

        $apiResponse = Http::withToken(config('discord.token'), 'Bot')
            ->withBody($eventDataJson, 'application/json')
            ->post($url);

        // 🔟 Check for API errors
        if ($apiResponse->failed()) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to create event.',
            ]);
            throw new Exception('Failed to create event. ' . json_encode($apiResponse->json()));
        }

        // ✅ Send an Embedded Confirmation Message
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '🎉 Event Created!',
            'embed_description' => "**Event:** {$eventTopic}\n**Start:** {$startDate} at {$startTime} UTC\n**Location:** " . ($isVoiceChannel ? '🔊 Voice Channel' : '🌍 External/Text Channel'),
        ]);
    }
}
