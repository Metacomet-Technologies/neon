<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Jobs\NativeCommand\Base\ProcessBaseJob;
use Exception;

final class ProcessDeleteEventJob extends ProcessBaseJob
{
    private readonly ?string $eventId;

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

        // Parse event ID in constructor
        $this->eventId = $this->parseEventId($messageContent);
    }

    protected function executeCommand(): void
    {
        // 1. Check permissions
        $member = $this->getDiscord()->guild($this->guildId)->member($this->discordUserId);
        if (! $member->canManageEvents()) {
            $this->sendPermissionDenied('delete events');
            throw new Exception('User does not have permission to delete events.', 403);
        }

        // 2. Validate event ID
        if (! $this->eventId) {
            $this->sendUsageAndExample();
            throw new Exception('No event ID provided.', 400);
        }

        // 3. Check if event exists
        $event = $this->getDiscord()->findEventById($this->guildId, $this->eventId);
        if (! $event) {
            $this->sendErrorMessage("Event with ID `{$this->eventId}` not found.");
            throw new Exception('Event not found.', 404);
        }

        // 4. Delete the event
        $success = $this->getDiscord()->deleteEvent($this->guildId, $this->eventId);

        if (! $success) {
            $this->sendApiError('delete event');
            throw new Exception('Failed to delete event.', 500);
        }

        // 5. Send confirmation message
        $this->sendEventDeletedMessage($event);
    }

    private function parseEventId(string $message): ?string
    {
        // Remove invisible characters and normalize spaces
        $cleanedMessage = preg_replace('/[\p{Cf}]/u', '', $message);
        $cleanedMessage = trim(preg_replace('/\s+/', ' ', $cleanedMessage));

        // Extract event ID
        preg_match('/^!delete-event\s+(\d{17,19})$/iu', $cleanedMessage, $matches);

        return $matches[1] ?? null;
    }

    private function sendEventDeletedMessage(array $event): void
    {
        $eventName = $event['name'] ?? 'Unknown Event';

        $this->sendSuccessMessage(
            'Event Deleted Successfully!',
            "**{$eventName}** has been removed.\n\n" .
            "**Event ID:** `{$this->eventId}`",
            15158332 // Red
        );
    }
}
