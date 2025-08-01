<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Jobs\NativeCommand\Base\ProcessBaseJob;
use DateTime;
use DateTimeZone;
use Exception;

final class ProcessNewEventJob extends ProcessBaseJob
{
    private readonly ?string $eventTopic;
    private readonly ?string $startDate;
    private readonly ?string $startTime;
    private readonly ?string $eventFrequency;
    private readonly ?string $location;
    private readonly ?string $description;
    private readonly ?string $coverImage;
    private readonly array $parsedData;

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

        // Parse event data in constructor
        $this->parsedData = $this->parseEventData($messageContent);
        $this->eventTopic = $this->parsedData['topic'] ?? null;
        $this->startDate = $this->parsedData['date'] ?? null;
        $this->startTime = $this->parsedData['time'] ?? null;
        $this->eventFrequency = $this->parsedData['frequency'] ?? null;
        $this->location = $this->parsedData['location'] ?? null;
        $this->description = $this->parsedData['description'] ?? null;
        $this->coverImage = $this->parsedData['coverImage'] ?? null;
    }

    protected function executeCommand(): void
    {
        // 1. Check permissions
        $member = $this->getDiscord()->guild($this->guildId)->member($this->discordUserId);
        if (! $member->canManageEvents()) {
            $this->sendPermissionDenied('create events');
            throw new Exception('User does not have permission to create events.', 403);
        }

        // 2. Validate required fields
        if (! $this->eventTopic || ! $this->startDate || ! $this->startTime ||
            ! $this->eventFrequency || ! $this->location || ! $this->description) {
            $this->sendUsageAndExample();
            throw new Exception('Missing required event parameters.', 400);
        }

        try {
            // 3. Check if location is a voice channel
            $channels = $this->getDiscord()->getGuildChannels($this->guildId);
            $voiceChannel = $channels->first(fn ($ch) => $ch['id'] === $this->location ||
                (isset($ch['name']) && $ch['name'] === $this->location)
            );

            $isVoiceChannel = $voiceChannel && $voiceChannel['type'] === 2;

            // 4. Parse datetime
            $startDateTime = $this->parseDateTime($this->startDate, $this->startTime);

            // 5. Build event data
            $eventData = $this->buildEventData($startDateTime, $isVoiceChannel, $voiceChannel);

            // 6. Create the event
            $event = $this->getDiscord()->createEvent($this->guildId, $eventData);

            if (! $event) {
                $this->sendApiError('create event');
                throw new Exception('Failed to create event.', 500);
            }

            // 7. Send success message
            $this->sendEventCreatedMessage($event);

        } catch (Exception $e) {
            $this->sendErrorMessage('Failed to create event: ' . $e->getMessage());
            throw new Exception('Failed to create Discord event.', 500);
        }
    }

    private function parseEventData(string $messageContent): array
    {
        $parts = explode('|', $messageContent);
        if (count($parts) < 6) {
            return [];
        }

        return [
            'topic' => trim(str_replace('!create-event', '', $parts[0]), ' "'),
            'date' => trim($parts[1]),
            'time' => trim($parts[2]),
            'frequency' => trim($parts[3]),
            'location' => trim($parts[4]),
            'description' => trim($parts[5], ' "'),
            'coverImage' => isset($parts[6]) ? trim($parts[6]) : null,
        ];
    }

    private function parseDateTime(string $date, string $time): DateTime
    {
        $dateTimeString = "{$date} {$time}";
        $dateTime = DateTime::createFromFormat('Y-m-d H:i', $dateTimeString, new DateTimeZone('UTC'));

        if (! $dateTime) {
            throw new Exception('Invalid date or time format. Use YYYY-MM-DD for date and HH:MM for time.');
        }

        return $dateTime;
    }

    private function buildEventData(DateTime $startDateTime, bool $isVoiceChannel, ?array $voiceChannel): array
    {
        $eventData = [
            'name' => $this->eventTopic,
            'scheduled_start_time' => $startDateTime->format('c'),
            'privacy_level' => 2, // GUILD_ONLY
            'entity_type' => $isVoiceChannel ? 2 : 3, // VOICE or EXTERNAL
            'description' => $this->description,
        ];

        if ($isVoiceChannel && $voiceChannel) {
            $eventData['channel_id'] = $voiceChannel['id'];
        } else {
            $eventData['entity_metadata'] = ['location' => $this->location];
        }

        if ($this->coverImage) {
            $eventData['image'] = $this->coverImage;
        }

        return $eventData;
    }

    private function sendEventCreatedMessage(array $event): void
    {
        $locationInfo = isset($event['channel_id'])
            ? "<#{$event['channel_id']}>"
            : ($event['entity_metadata']['location'] ?? 'Unknown Location');

        $embedDescription = "**{$event['name']}** has been scheduled!\n\n";
        $embedDescription .= "**📅 Date & Time**\n<t:{$event['scheduled_start_time']}:F>\n\n";
        $embedDescription .= "**📍 Location**\n{$locationInfo}\n\n";
        $embedDescription .= "**📝 Description**\n" . ($event['description'] ?? 'No description');

        $this->sendSuccessMessage(
            'Event Created Successfully!',
            $embedDescription,
            5814783 // Purple
        );
    }
}
