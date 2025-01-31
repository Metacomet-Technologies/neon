<?php

declare(strict_types=1);

namespace App\Helpers;

final class DiscordChannelValidator
{
    /**
     * Validate if a Discord channel name is valid.
     *
     * @return array{is_valid: bool, message: string}
     */
    public static function validateChannelName(string $channelName): array
    {
        $maxLength = 100;
        $pattern = '/^[a-z0-9_-]+$/';

        if (strlen($channelName) > $maxLength) {
            return [
                'is_valid' => false,
                'message' => "The channel name must not exceed {$maxLength} characters.",
            ];
        }

        if (! preg_match($pattern, $channelName)) {
            return [
                'is_valid' => false,
                'message' => 'The channel name contains invalid characters. Only lowercase letters, numbers, hyphens, and underscores are allowed.',
            ];
        }

        return [
            'is_valid' => true,
            'message' => 'The channel name is valid.',
        ];
    }
}
