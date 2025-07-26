<?php

declare(strict_types=1);

namespace App\Traits;

use App\Helpers\Discord\SendMessage;
use App\Services\DiscordParserService;
use DateTime;
use Exception;

trait DiscordValidatorTrait
{
    /**
     * Validate that a user ID is in correct Discord format.
     */
    protected function validateUserId(string $userId, string $context = 'user'): void
    {
        if (! DiscordParserService::isValidDiscordId($userId)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Invalid {$context} ID format. Please provide a valid Discord {$context} ID.",
            ]);
            throw new Exception("Invalid {$context} ID format.", 400);
        }
    }

    /**
     * Validate that a channel ID is in correct Discord format.
     */
    protected function validateChannelId(string $channelId): void
    {
        $this->validateUserId($channelId, 'channel');
    }

    /**
     * Validate that required parameters are provided.
     */
    protected function validateRequiredParameters(array $parameters, int $minCount, ?string $customMessage = null): void
    {
        if (count($parameters) < $minCount) {
            if ($customMessage) {
                SendMessage::sendMessage($this->channelId, [
                    'is_embed' => false,
                    'response' => "❌ {$customMessage}",
                ]);
            } else {
                $this->sendUsageAndExample();
            }
            throw new Exception('Insufficient parameters provided.', 400);
        }
    }

    /**
     * Validate that a target exists and is not null.
     */
    protected function validateTarget($target, string $type, string $identifier): void
    {
        if (! $target) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ {$type} '{$identifier}' not found.",
            ]);
            throw new Exception("{$type} not found.", 404);
        }
    }

    /**
     * Validate that a user has a valid mention format.
     */
    protected function validateUserMentions(array $mentions): array
    {
        $userIds = [];

        foreach ($mentions as $mention) {
            $userId = DiscordParserService::extractUserId($mention);
            if (! $userId) {
                SendMessage::sendMessage($this->channelId, [
                    'is_embed' => false,
                    'response' => "❌ Invalid user mention format: {$mention}",
                ]);
                throw new Exception('Invalid user mention format.', 400);
            }
            $userIds[] = $userId;
        }

        return $userIds;
    }

    /**
     * Validate numeric input within a range.
     */
    protected function validateNumericRange(string $value, int $min, int $max, string $context): int
    {
        if (! is_numeric($value)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ {$context} must be a number between {$min} and {$max}.",
            ]);
            throw new Exception('Invalid numeric value.', 400);
        }

        $numValue = (int) $value;

        if ($numValue < $min || $numValue > $max) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ {$context} must be between {$min} and {$max}.",
            ]);
            throw new Exception('Value out of range.', 400);
        }

        return $numValue;
    }

    /**
     * Validate boolean-like input (true/false, yes/no, 1/0).
     */
    protected function validateBoolean(string $value, string $context): bool
    {
        $value = strtolower(trim($value));

        if (in_array($value, ['true', '1', 'yes', 'on'])) {
            return true;
        }

        if (in_array($value, ['false', '0', 'no', 'off'])) {
            return false;
        }

        SendMessage::sendMessage($this->channelId, [
            'is_embed' => false,
            'response' => "❌ {$context} must be true/false, yes/no, or 1/0.",
        ]);
        throw new Exception('Invalid boolean value.', 400);
    }

    /**
     * Validate date format (YYYY-MM-DD).
     */
    protected function validateDate(string $date): DateTime
    {
        $dateTime = DateTime::createFromFormat('Y-m-d', $date);

        if (! $dateTime || $dateTime->format('Y-m-d') !== $date) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Invalid date format. Use YYYY-MM-DD format.',
            ]);
            throw new Exception('Invalid date format.', 400);
        }

        return $dateTime;
    }

    /**
     * Validate time format (HH:MM).
     */
    protected function validateTime(string $time): array
    {
        if (! preg_match('/^(\d{1,2}):(\d{2})$/', $time, $matches)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Invalid time format. Use HH:MM format.',
            ]);
            throw new Exception('Invalid time format.', 400);
        }

        $hour = (int) $matches[1];
        $minute = (int) $matches[2];

        if ($hour > 23 || $minute > 59) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Invalid time. Hour must be 0-23, minute must be 0-59.',
            ]);
            throw new Exception('Invalid time values.', 400);
        }

        return [$hour, $minute];
    }
}
