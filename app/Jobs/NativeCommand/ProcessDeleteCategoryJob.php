<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;


use App\Jobs\NativeCommand\ProcessBaseJob;
use App\Services\Discord\Discord;
use Exception;
use Illuminate\Support\Facades\Log;

final class ProcessDeleteCategoryJob extends ProcessBaseJob
{
    public function __construct(
        string $discordUserId,
        string $channelId,
        string $guildId,
        string $messageContent,
        array $command,
        string $commandSlug,
        array $parameters = []
    ) {
        parent::__construct($discordUserId, $channelId, $guildId, $messageContent, $command, $commandSlug, $parameters);
    }

    protected function executeCommand(): void
    {
        // Ensure the user has permission to manage channels
        $discord = new Discord;
        if (! $discord->guild($this->guildId)->member($this->discordUserId)->canManageChannels()) {
            $discord->channel($this->channelId)->send('❌ You do not have permission to manage categories in this server.');
            throw new Exception('User does not have permission to manage categories in this server.', 403);
        }
        // Parse the command
        $parts = explode(' ', $this->messageContent);

        if (count($parts) < 2) {
            $this->sendUsageAndExample();

            throw new Exception('No category ID provided.', 400);
        }
        $categoryId = $parts[1];

        // Ensure the provided category ID is numeric
        if (! is_numeric($categoryId)) {
            $discord->channel($this->channelId)->send('❌ Invalid category ID. Please provide a valid numeric ID.');
            throw new Exception('Operation failed', 500);
        }
        // Fetch all channels to verify the category exists and is a category
        $discord = new Discord;
        
        try {
            $channels = collect($discord->guild($this->guildId)->channels());
            
            if (!$channels) {
                Log::error("Failed to fetch channels for guild {$this->guildId}");
                $discord->channel($this->channelId)->send('❌ Failed to retrieve channels from the server.');
                throw new Exception('Operation failed', 500);
            }
        } catch (Exception $e) {
            Log::error("Failed to fetch channels for guild {$this->guildId}", ['error' => $e->getMessage()]);
            $discord->channel($this->channelId)->send('❌ Failed to retrieve channels from the server.');
            throw new Exception('Operation failed', 500);
        }

        // 4️⃣ Find the category by ID and confirm it is a category (Type 4)
        $category = $channels->first(fn ($c) => $c['id'] === $categoryId && $c['type'] === 4);

        if (! $category) {
            $discord->channel($this->channelId)->send("❌ No category found with ID `{$categoryId}`.");
            throw new Exception('Category not found in guild.', 404);
        }
        // 5️⃣ Check if category has child channels
        $childChannels = $channels->filter(fn ($c) => $c['parent_id'] === $categoryId);

        if ($childChannels->isNotEmpty()) {
            $channelList = $childChannels->map(fn ($c) => "`{$c['name']}` (ID: `{$c['id']}`)")->implode("\n");

            $discord->channel($this->channelId)->send("❌ Category ID `{$categoryId}` contains channels:\n{$channelList}\nPlease delete or move them first.");
            throw new Exception('Category contained child channels.', 400);
        }
        // Make the delete request
        try {
            $discord->channel($categoryId)->delete();
        } catch (Exception $e) {
            Log::error("Exception while deleting category '{$categoryId}' in guild {$this->guildId}", ['error' => $e->getMessage()]);
            $discord->channel($this->channelId)->send("❌ Failed to delete category (ID: `{$categoryId}`).");
            throw new Exception('Operation failed', 500);
        }
        // ✅ Success! Send confirmation message
        $discord->channel($this->channelId)->sendEmbed(
            '✅ Category Deleted!',
            "**Category ID:** `{$categoryId}` has been successfully removed.",
            15158332 // Red embed
        );
    }
}
