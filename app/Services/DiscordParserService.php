<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Service for parsing Discord entities from message content.
 */
final class DiscordParserService
{
    /**
     * Extract user ID from mention or direct ID.
     */
    public static function extractUserId(string $input): ?string
    {
        // Match user mention <@123456789> or <@!123456789> or direct ID
        if (preg_match('/^<@!?(\d{17,19})>$/', trim($input), $matches)) {
            return $matches[1];
        }

        // Match direct user ID
        if (preg_match('/^(\d{17,19})$/', trim($input), $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extract multiple user IDs from mentions.
     */
    public static function extractUserIds(array $mentions): array
    {
        $userIds = [];

        foreach ($mentions as $mention) {
            $userId = self::extractUserId($mention);
            if ($userId) {
                $userIds[] = $userId;
            }
        }

        return $userIds;
    }

    /**
     * Extract channel ID from mention or direct ID.
     */
    public static function extractChannelId(string $input): ?string
    {
        // Match channel mention <#123456789>
        if (preg_match('/^<#(\d{17,19})>$/', trim($input), $matches)) {
            return $matches[1];
        }

        // Match direct channel ID
        if (preg_match('/^(\d{17,19})$/', trim($input), $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extract role ID from mention or return role name.
     */
    public static function extractRoleIdentifier(string $input): array
    {
        // Match role mention <@&123456789>
        if (preg_match('/^<@&(\d{17,19})>$/', trim($input), $matches)) {
            return ['type' => 'id', 'value' => $matches[1]];
        }

        // Return as role name
        return ['type' => 'name', 'value' => trim($input)];
    }

    /**
     * Parse command with user target (ban, kick, mute, etc.).
     */
    public static function parseUserCommand(string $messageContent, string $command): ?string
    {
        $pattern = "/^!{$command}\s+(<@!?(\d{17,19})>|\d{17,19})$/";

        if (preg_match($pattern, $messageContent, $matches)) {
            return $matches[2] ?? $matches[1] ?? null;
        }

        return null;
    }

    /**
     * Parse command with channel and new name (edit-channel-name, etc.).
     */
    public static function parseChannelEditCommand(string $messageContent, string $command): array
    {
        $pattern = "/^!{$command}\s+(<#(\d{17,19})>|\d{17,19})\s+(.+)$/";

        if (preg_match($pattern, $messageContent, $matches)) {
            $channelId = $matches[2] ?? $matches[1];
            $newValue = trim($matches[3]);

            return [$channelId, $newValue];
        }

        return [null, null];
    }

    /**
     * Parse role assignment command (assign-role, remove-role).
     */
    public static function parseRoleCommand(string $messageContent, string $command): array
    {
        $parts = explode(' ', $messageContent);

        if (count($parts) < 3) {
            return [null, []];
        }

        $roleName = $parts[1];
        $userMentions = array_slice($parts, 2);
        $userIds = self::extractUserIds($userMentions);

        return [$roleName, $userIds];
    }

    /**
     * Parse scheduled message command with channel, datetime, and message.
     */
    public static function parseScheduledMessageCommand(string $messageContent): array
    {
        // Pattern: !scheduled-message <#channel> <YYYY-MM-DD HH:MM> <message>
        $pattern = '/^!scheduled-message\s+(<#(\d{17,19})>)\s+(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2})\s+(.+)$/';

        if (preg_match($pattern, $messageContent, $matches)) {
            return [
                'channel_id' => $matches[2],
                'datetime' => $matches[3],
                'message' => trim($matches[4]),
            ];
        }

        return ['channel_id' => null, 'datetime' => null, 'message' => null];
    }

    /**
     * Parse notify command with complex structure.
     */
    public static function parseNotifyCommand(string $messageContent): array
    {
        // Pattern: !notify <#channel> <mentions> [title] | <message>
        $pattern = '/^!notify\s+(<#(\d{17,19})>)\s+([^|]+)\|(.+)$/';

        if (preg_match($pattern, $messageContent, $matches)) {
            $channelId = $matches[2];
            $mentionsPart = trim($matches[3]);
            $messagePart = trim($matches[4]);

            // Check if there's a title before the message
            $titleAndMessage = explode('|', $messagePart, 2);
            if (count($titleAndMessage) === 2) {
                $title = trim($titleAndMessage[0]);
                $message = trim($titleAndMessage[1]);
            } else {
                $title = null;
                $message = $messagePart;
            }

            return [
                'channel_id' => $channelId,
                'mentions' => $mentionsPart,
                'title' => $title,
                'message' => $message,
            ];
        }

        return ['channel_id' => null, 'mentions' => null, 'title' => null, 'message' => null];
    }

    /**
     * Validate Discord ID format (17-19 digits).
     */
    public static function isValidDiscordId(string $id): bool
    {
        return preg_match('/^\d{17,19}$/', $id) === 1;
    }

    /**
     * Extract command parameters after command name.
     */
    public static function extractParameters(string $messageContent, string $command): array
    {
        $pattern = "/^!{$command}\s+(.+)$/";

        if (preg_match($pattern, $messageContent, $matches)) {
            return array_filter(explode(' ', trim($matches[1])));
        }

        return [];
    }
}
