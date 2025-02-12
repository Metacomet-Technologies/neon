<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\SendMessage;
use App\Models\NativeCommandRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;

final class ProcessColorJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public array $colors = [
        'White' => '#FFFFFF', 'Black' => '#000000', 'Red' => '#FF0000', 'Green' => '#00FF00',
        'Blue' => '#0000FF', 'Yellow' => '#FFFF00', 'Cyan' => '#00FFFF', 'Magenta' => '#FF00FF',
        'Orange' => '#FFA500', 'Purple' => '#800080', 'Teal' => '#008080', 'Olive' => '#808000',
        'Gray' => '#808080', 'Silver' => '#C0C0C0', 'Maroon' => '#800000', 'Navy' => '#000080',
        'Turquoise' => '#40E0D0', 'Violet' => '#EE82EE', 'Indigo' => '#4B0082', 'Chartreuse' => '#7FFF00',
        'Gold' => '#FFD700', 'Coral' => '#FF7F50', 'Salmon' => '#FA8072', 'Khaki' => '#F0E68C',
        'Orchid' => '#DA70D6', 'Lavender' => '#E6E6FA', 'Linen' => '#FAF0E6', 'Chocolate' => '#D2691E',
        'Tomato' => '#FF6347', 'Beige' => '#F5F5DC', 'Crimson' => '#DC143C', 'Deep Pink' => '#FF1493',
        'Dodger Blue' => '#1E90FF', 'Fire Brick' => '#B22222', 'Forest Green' => '#228B22', 'Peru' => '#CD853F',
        'Sienna' => '#A0522D', 'Slate Blue' => '#6A5ACD', 'Slate Gray' => '#708090', 'Spring Green' => '#00FF7F',
        'Steel Blue' => '#4682B4', 'Tan' => '#D2B48C', 'Thistle' => '#D8BFD8', 'Medium Aquamarine' => '#66CDAA',
        'Medium Blue' => '#0000CD', 'Medium Orchid' => '#BA55D3', 'Medium Purple' => '#9370DB',
        'Medium Sea Green' => '#3CB371', 'Midnight Blue' => '#191970', 'Honeydew' => '#F0FFF0',
    ];

    public array $command;

    public function __construct(
        public NativeCommandRequest $nativeCommandRequest
    ) {
        $this->command = $this->nativeCommandRequest->command;
    }

    public function handle(): void
    {
        $trimmedMessage = strtolower(trim($this->nativeCommandRequest->message_content));
        $parts = explode(' ', $trimmedMessage);

        // If the user sends only "!color", return the usage and example message
        if (count($parts) < 2) {
            SendMessage::sendMessage($this->nativeCommandRequest->channel_id, [
                'is_embed' => false,
                'response' => $this->command['usage'] . "\n" . $this->command['example'],
            ]);

            $this->nativeCommandRequest->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => [
                    'message' => 'No color specified in the command.',
                    'status_code' => 418, // "I'm a teapot" (fun but meaningful)
                ],
            ]);

            return;
        }

        // If the user requests "!color list"
        if ($parts[1] === 'list') {
            $responseLines = array_map(fn ($name, $hex) => "**{$name}** : `{$hex}`", array_keys($this->colors), $this->colors);
            SendMessage::sendMessage($this->nativeCommandRequest->channel_id, [
                'is_embed' => false,
                'response' => implode("\n", $responseLines),
            ]);

            $this->nativeCommandRequest->update([
                'status' => 'completed',
                'executed_at' => now(),
            ]);

            return;
        }

        // Get the requested color name
        $requestedColor = trim(strtolower(implode(' ', array_slice($parts, 1))));

        // Search for the color
        $matchedColor = null;
        foreach ($this->colors as $name => $hex) {
            if (strcasecmp($name, $requestedColor) === 0) {
                $matchedColor = ['name' => $name, 'hex' => $hex];
                break;
            }
        }

        if ($matchedColor) {
            $hexCode = $matchedColor['hex'];
            $colorName = $matchedColor['name'];
            $colorDecimal = hexdec(ltrim($hexCode, '#'));

            SendMessage::sendMessage($this->nativeCommandRequest->channel_id, [
                'is_embed' => true,
                'embed_title' => "🎨 {$colorName}",
                'embed_description' => "Here is the hex code for **{$colorName}**: {$hexCode}",
                'embed_color' => $colorDecimal,
            ]);

            $this->nativeCommandRequest->update([
                'status' => 'completed',
                'executed_at' => now(),
            ]);

            return;
        }

        SendMessage::sendMessage($this->nativeCommandRequest->channel_id, [
            'is_embed' => false,
            'response' => "❌ Color **{$requestedColor}** not found. Try `!color list` to see available colors.",
        ]);

        $this->nativeCommandRequest->update([
            'status' => 'color-not-found',
            'failed_at' => now(),
            'error_message' => [
                'message' => "Color '{$requestedColor}' not found.",
                'status_code' => 400,
            ],
        ]);
    }
}
