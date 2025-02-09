<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\SendMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

final class ProcessColorJob implements ShouldQueue
{
    use InteractsWithQueue;

    public string $usageMessage;
    public string $exampleMessage;

    public array $colors = [
        'White' => '#FFFFFF',
        'Black' => '#000000',
        'Red' => '#FF0000',
        'Green' => '#00FF00',
        'Blue' => '#0000FF',
        'Yellow' => '#FFFF00',
        'Cyan' => '#00FFFF',
        'Magenta' => '#FF00FF',
        'Orange' => '#FFA500',
        'Purple' => '#800080',
        'Teal' => '#008080',
        'Olive' => '#808000',
        'Gray' => '#808080',
        'Silver' => '#C0C0C0',
        'Maroon' => '#800000',
        'Navy' => '#000080',
        'Turquoise' => '#40E0D0',
        'Violet' => '#EE82EE',
        'Indigo' => '#4B0082',
        'Chartreuse' => '#7FFF00',
        'Gold' => '#FFD700',
        'Coral' => '#FF7F50',
        'Salmon' => '#FA8072',
        'Khaki' => '#F0E68C',
        'Orchid' => '#DA70D6',
        'Lavender' => '#E6E6FA',
        'Linen' => '#FAF0E6',
        'Chocolate' => '#D2691E',
        'Tomato' => '#FF6347',
        'Beige' => '#F5F5DC',
        'Crimson' => '#DC143C',
        'Deep Pink' => '#FF1493',
        'Dodger Blue' => '#1E90FF',
        'Fire Brick' => '#B22222',
        'Forest Green' => '#228B22',
        'Peru' => '#CD853F',
        'Sienna' => '#A0522D',
        'Slate Blue' => '#6A5ACD',
        'Slate Gray' => '#708090',
        'Spring Green' => '#00FF7F',
        'Steel Blue' => '#4682B4',
        'Tan' => '#D2B48C',
        'Thistle' => '#D8BFD8',
        'Medium Aquamarine' => '#66CDAA',
        'Medium Blue' => '#0000CD',
        'Medium Orchid' => '#BA55D3',
        'Medium Purple' => '#9370DB',
        'Medium Sea Green' => '#3CB371',
        'Midnight Blue' => '#191970',
        'Honeydew' => '#F0FFF0',
    ];

    public function __construct(
        public string $discordUserId,
        public string $channelId,
        public string $guildId,
        public string $messageContent,
    ) {
        // Fetch command details from the database
        $command = DB::table('native_commands')->where('slug', 'color')->first();

        // dump('Fetched command from database', $command);

        $this->usageMessage = $command->usage ?? 'Usage information not available.';
        $this->exampleMessage = $command->example ?? 'Example not available.';
    }

    // 'slug' => 'color',
    // 'description' => 'Displays a list of 50 hex colors with their corresponding names.',
    // 'class' => \App\Jobs\ProcessColorJob::class,
    // 'usage' => 'Usage: !color list | <color-name>',
    // 'example' => 'Example: !color list or !color red',
    // 'is_active' => true,

    public function handle(): void
    {
        // dump('Starting ProcessColorJob', [
        //     'User ID' => $this->discordUserId,
        //     'Channel ID' => $this->channelId,
        //     'Guild ID' => $this->guildId,
        //     'Raw Message Content' => $this->messageContent,
        // ]);

        // Convert message content to lowercase and trim spaces
        $trimmedMessage = strtolower(trim($this->messageContent));
        // dump('Trimmed Message', $trimmedMessage);

        // Split message into parts based on spaces
        $parts = explode(' ', $trimmedMessage);
        // dump('Split Message Parts', $parts);

        // If the user sends only "!color", return the usage and example message
        if (count($parts) < 2) {
            // dump('User only typed !color, returning usage message');

            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => $this->usageMessage,
            ]);
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => $this->exampleMessage,
            ]);

            return;
        }

        // If the user requests "!color list"
        if ($parts[1] === 'list') {
            // dump('User requested color list');

            $responseLines = [];
            foreach ($this->colors as $name => $hex) {
                $responseLines[] = "**{$name}** : `{$hex}`";
            }
            $responseMessage = implode("\n", $responseLines);

            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => $responseMessage,
            ]);

            return;
        }

        // Get the requested color name
        $requestedColor = trim(strtolower(implode(' ', array_slice($parts, 1))));
        // dump('User requested color', $requestedColor);

        // Search for the color
        $matchedColor = null;
        foreach ($this->colors as $name => $hex) {
            if (strcasecmp($name, $requestedColor) === 0) {
                $matchedColor = ['name' => $name, 'hex' => $hex];
                break;
            }
        }

        // dump('Matched color', $matchedColor);

        if ($matchedColor) {
            $hexCode = $matchedColor['hex'];
            $colorName = $matchedColor['name'];
            $colorDecimal = hexdec(ltrim($hexCode, '#'));

            // dump('Final Embed Payload', [
            //     'embed_title' => "🎨 {$colorName}",
            //     'embed_description' => "Here is the hex code for **{$colorName}**.",
            //     'embed_color' => $colorDecimal,
            // ]);

            // ✅ Use `embed_title`, `embed_description`, and `embed_color` for compatibility
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => true,
                'embed_title' => "🎨 {$colorName}",
                'embed_description' => "Here is the hex code for **{$colorName}**: {$hexCode}",
                'embed_color' => $colorDecimal,
            ]);

            return;
        }

        // dump('Color not found', $requestedColor);

        SendMessage::sendMessage($this->channelId, [
            'is_embed' => false,
            'response' => "❌ Color **{$requestedColor}** not found. Try `!color list` to see available colors.",
        ]);
    }
}
