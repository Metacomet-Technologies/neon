<?php

declare(strict_types=1);

namespace App\Helpers\Discord;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class SendMessage
{
    /**
     * Get all guilds for the user.
     *
     * @param array{
     *     is_embed: bool,
     *     embed_title?: string,
     *     embed_description?: string,
     *     embed_color?: int,
     *     response?: string,
     * } $command
     */
    public static function sendMessage(string $channelId, array $command): string
    {
        $baseUrl = config('services.discord.rest_api_url');
        $url = $baseUrl . '/channels/' . $channelId . '/messages';

        if (! isset($command['is_embed'])) {
            Log::error('Embed indicator is required for messages', [
                'channel_id' => $channelId,
                'command' => $command,
            ]);

            return 'failed';
        }

        $isEmbed = $command['is_embed'] ?? false;

        $body = [];
        if ($isEmbed) {
            $title = $command['embed_title'] ?? 'Title';
            $description = $command['embed_description'] ?? 'Description';
            $color = $command['embed_color'] ?? 57358;

            $embed = [
                'title' => $title,
                'description' => $description,
                'color' => $color,
                'footer' => [
                    'text' => self::setMessageOutput('Sent from Neon'),
                ],
            ];

            $body = [
                'embeds' => [$embed],
            ];

        } else {
            if (! isset($command['response'])) {
                Log::error('Response is required for non-embed messages', [
                    'channel_id' => $channelId,
                    'command' => $command,
                ]);

                return 'failed';
            }
            $response = $command['response'];
            $body = [
                'content' => self::setMessageOutput($response),
            ];
        }

        // Send the embedded message
        $apiResponse = Http::withToken(config('discord.token'), 'Bot')
            ->post($url, $body);

        // Log result and return status
        if ($apiResponse->failed()) {
            Log::error('Failed to send embedded message', [
                'channel_id' => $channelId,
                'body' => $body,
                'api_response' => $apiResponse->json(),
            ]);

            return 'failed';
        }

        Log::info('Sent embedded message', [
            'channel_id' => $channelId,
            'body' => $body,
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
