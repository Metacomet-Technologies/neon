<?php

declare(strict_types=1);

namespace App\Helpers\Discord;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class SendMessage
{
    /**
     * Get all guilds for the user.
     */
    public static function sendMessage(string $message, string $channelId): string
    {
        $baseUrl = config('services.discord.rest_api_url');
        $url = $baseUrl . '/channels/' . $channelId . '/messages';
        $apiResponse = Http::withToken(config('discord.token'), 'Bot')
            ->post($url, [
                'content' => self::setMessageOutput($message),
            ]);

        if ($apiResponse->failed()) {
            Log::error('Failed to send message to Discord', [
                'channel_id' => $channelId,
                'message' => $message,
                'api_response' => $apiResponse->json(),
            ]);

            return 'failed';
        }

        Log::info('Sent message to Discord', [
            'channel_id' => $channelId,
            'message' => $message,
            'api_response' => $apiResponse->json(),
        ]);

        return 'sent';
    }

    /**
     * Set the message output based on the environment.
     */
    public static function setMessageOutput(string $message): string
    {
        $environment = config('app.env');
        if ($environment === 'production') {
            return $message;
        }

        return '[' . $environment . '] ' . $message;
    }
}
