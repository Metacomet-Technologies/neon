<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Services\Discord\Discord;
use Exception;

final class ProcessColorJob extends ProcessBaseJob
{
    private array $colors = [
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

    protected function executeCommand(): void
    {
        // No permission check needed for color lookup

        $params = Discord::extractParameters($this->messageContent, 'color');

        // If no parameters, show usage
        if (empty($params)) {
            $this->sendUsageAndExample();
            throw new Exception('No color specified in the command.', 400);
        }

        $query = strtolower(implode(' ', $params));

        // Handle "list" request
        if ($query === 'list') {
            $this->displayColorList();

            return;
        }

        // Find specific color
        $matchedColor = $this->findColor($query);

        if ($matchedColor) {
            $this->displayColor($matchedColor);
        } else {
            $this->sendErrorMessage("Color '{$query}' not found. Try `!color list` to see available colors.");
            throw new Exception("Color '{$query}' not found.", 400);
        }
    }

    /**
     * Display the list of available colors.
     */
    private function displayColorList(): void
    {
        $colorList = [];
        foreach ($this->colors as $name => $hex) {
            $colorList[] = "**{$name}** : `{$hex}`";
        }

        $this->sendListMessage('Available Colors', $colorList);
    }

    /**
     * Find a color by name (case-insensitive).
     */
    private function findColor(string $query): ?array
    {
        foreach ($this->colors as $name => $hex) {
            if (strcasecmp($name, $query) === 0) {
                return ['name' => $name, 'hex' => $hex];
            }
        }

        return null;
    }

    /**
     * Display a specific color with its information.
     */
    private function displayColor(array $color): void
    {
        $colorDecimal = hexdec(ltrim($color['hex'], '#'));

        $this->sendSuccessMessage(
            "🎨 {$color['name']}",
            "Here is the hex code for **{$color['name']}**: {$color['hex']}",
            $colorDecimal
        );
    }
}
