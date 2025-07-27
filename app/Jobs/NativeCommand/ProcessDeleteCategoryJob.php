<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Jobs\NativeCommand\Base\ProcessBaseJob;
use Exception;

final class ProcessDeleteCategoryJob extends ProcessBaseJob
{
    protected function executeCommand(): void
    {
        // 1. Check permissions
        $this->requireChannelPermission();

        // 2. Parse the command
        $parts = explode(' ', $this->messageContent);
        if (count($parts) < 2) {
            $this->sendUsageAndExample();
            throw new Exception('No category ID provided.', 400);
        }

        $categoryId = $parts[1];

        // 3. Validate category ID
        $this->validateChannelId($categoryId);

        // 4. Fetch all channels to verify it's a category and check for children
        $channels = $this->getDiscord()->getGuildChannels($this->guildId);

        // Find the category by ID and confirm it is a category (Type 4)
        $category = $channels->first(fn ($c) => $c['id'] === $categoryId && $c['type'] === 4);

        if (! $category) {
            $this->sendNotFound('Category', $categoryId);
            throw new Exception('Category not found in guild.', 404);
        }

        // 5. Check if category has child channels
        $childChannels = $channels->filter(fn ($c) => $c['parent_id'] === $categoryId);

        if ($childChannels->isNotEmpty()) {
            $channelList = $childChannels->map(fn ($c) => "`{$c['name']}` (ID: `{$c['id']}`)")->implode("\n");
            $this->sendErrorMessage("Category contains channels:\n{$channelList}\nPlease delete or move them first.");
            throw new Exception('Category contained child channels.', 400);
        }

        // 6. Delete the category
        $success = $this->getDiscord()->deleteChannel($categoryId);

        if (! $success) {
            $this->sendApiError('delete category');
            throw new Exception('Failed to delete category.', 500);
        }

        // 7. Send confirmation
        $this->sendSuccessMessage(
            'Category Deleted!',
            "**Category ID:** `{$categoryId}` has been successfully removed.",
            15158332 // Red color
        );
    }
}
