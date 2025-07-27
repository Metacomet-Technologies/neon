<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Jobs\NativeCommand\Base\ProcessBaseJob;
use Exception;

final class ProcessNewCategoryJob extends ProcessBaseJob
{
    protected function executeCommand(): void
    {
        // 1. Check permissions
        $this->requireChannelPermission();

        // 2. Parse the command to get category name
        $parts = explode(' ', $this->messageContent, 2);
        if (count($parts) < 2) {
            $this->sendUsageAndExample();
            throw new Exception('No category name provided.', 400);
        }

        $categoryName = trim($parts[1]);

        // 3. Create the category using Discord service
        $createdCategory = $this->getDiscord()->createCategory($this->guildId, $categoryName);

        if (! $createdCategory) {
            $this->sendApiError('create category');
            throw new Exception('Failed to create category.', 500);
        }

        // 4. Send confirmation
        $this->sendSuccessMessage(
            'Category Created!',
            "**Category Name:** 📂 {$categoryName}"
        );
    }
}
