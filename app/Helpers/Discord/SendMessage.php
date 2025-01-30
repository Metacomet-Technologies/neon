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
    public static function sendEmbedMessage(string $channelId, string $title, string $description): string
    {
        $baseUrl = config('services.discord.rest_api_url');
        $url = $baseUrl . '/channels/' . $channelId . '/messages';

        // Define the embed structure
        $embed = [
            "title" => $title,
            "description" => $description,
            "color" => 16711935,
            "footer" => [
                "text" => "Sent from Neon",
            ]
        ];

        // Send the embedded message
        $apiResponse = Http::withToken(config('discord.token'), 'Bot')
            ->post($url, [
                'embeds' => [$embed], // Discord requires embeds to be an array
            ]);

        // Log result and return status
        if ($apiResponse->failed()) {
            Log::error('Failed to send embedded message', [
                'channel_id' => $channelId,
                'title' => $title,
                'description' => $description,
                'api_response' => $apiResponse->json(),
            ]);
            return 'failed';
        }

        Log::info('Sent embedded message', [
            'channel_id' => $channelId,
            'title' => $title,
            'description' => $description,
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
