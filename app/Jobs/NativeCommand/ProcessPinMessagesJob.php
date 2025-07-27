<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Services\Discord\DiscordService;
use Exception;

final class ProcessPinMessagesJob extends ProcessBaseJob
{
    private string $messageId = ''; // ✅ Default to empty string to prevent null error

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
        $this->messageId = $this->parseMessage($this->messageContent) ?? ''; // ✅ Ensures it's never null

        // 🚨 **Validation: Prevent execution if no message ID is found**
        if (empty($this->messageId)) {
            $this->sendUsageAndExample();

            throw new Exception('Operation failed', 500);
            throw new Exception('Invalid input for !pin. Expected a valid message ID or the keyword "this".');
        }
        // 1️⃣ Ensure the user has permission to pin messages
        $discord = app(DiscordService::class);
        $canManageChannels = $discord->guild($this->guildId)->member($this->discordUserId)->canManageChannels();
        if (! $canManageChannels) {
            $discord->channel($this->channelId)->send('❌ You are not allowed to pin messages.');
            throw new Exception('User does not have permission to manage channels', 403);
        }
        $this->pinMessage();
    }

    private function parseMessage(string $message): ?string
    {
        // Check if the command contains the keyword "this" to pin the last message
        if (stripos($message, 'this') !== false) {
            return $this->getLastMessageId();
        }
        // Otherwise, extract the message ID
        preg_match('/^!pin\s+(\d{17,19})$/', $message, $matches);

        return isset($matches[1]) ? $matches[1] : null;
    }

    private function getLastMessageId(): ?string
    {

        try {
            $discord = app(DiscordService::class);
            $messages = $discord->channel($this->channelId)->getMessages(['limit' => 2]);

            // The first message will be the last message (the current command)
            // The second message will be the message above it
            if (count($messages) < 2) {
                $discord->channel($this->channelId)->send('❌ There is no previous message to pin.');
                throw new Exception('Operation failed', 500);
            }

            $previousMessage = $messages[1]; // Get the second message (the one before the command)

            return $previousMessage['id'] ?? null;
        } catch (Exception $e) {
            $discord = app(DiscordService::class);
            $discord->channel($this->channelId)->send('❌ Failed to fetch the last message. Please try again later.');
            throw new Exception('Operation failed', 500);
        }
    }

    private function userHasPermission(string $userId): bool
    {
        $discord = app(DiscordService::class);
        $member = $discord->guild($this->guildId)->member($userId);

        // Check for both the ADMINISTRATOR and MANAGE_MESSAGES permissions
        if ($member->canManageChannels() || $member->canManageMessages()) {
            return true;
        }

        return false;
    }

    private function pinMessage(): void
    {

        try {
            $discord = app(DiscordService::class);
            $discord->channel($this->channelId)->pinMessage($this->messageId);

            // ✅ Success! Send confirmation message
            $discord->channel($this->channelId)->sendEmbed(
                '📌 Message Pinned',
                "✅ Successfully pinned message ID `{$this->messageId}` in this channel.",
                3066993
            );
        } catch (Exception $e) {
            $discord->channel($this->channelId)->send('❌ Failed to pin the message. Please try again later.');
            throw new Exception('Failed to pin the message.', 500);
        }
    }
}
