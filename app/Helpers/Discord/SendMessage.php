<?php

declare(strict_types=1);

namespace App\Helpers\Discord;

use App\Services\DiscordApiService;
use Illuminate\Support\Facades\Log;

final class SendMessage
{
    /**
     * Get all guilds for the user.
     *
     * @param  array<string, mixed>  $command
     */
    public static function sendMessage(string $channelId, array $command): string
    {
        $service = app(DiscordApiService::class);

        $body = [];
        if ($command['is_embed']) {
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
        try {
            $apiResponse = $service->post("/channels/{$channelId}/messages", $body);

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
        } catch (\Exception $e) {
            Log::error('Failed to send message due to rate limiting or other error', [
                'channel_id' => $channelId,
                'body' => $body,
                'error' => $e->getMessage(),
            ]);

            return 'failed';
        }
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
