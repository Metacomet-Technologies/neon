<?php

declare(strict_types=1);

namespace App\Traits;

use App\Services\Discord\DiscordService;

/**
 * Base trait for Discord functionality.
 * Provides shared Discord instance management.
 */
trait DiscordBaseTrait
{
    private ?DiscordService $discord = null;

    /**
     * Get Discord instance.
     */
    protected function getDiscord(): DiscordService
    {
        return $this->discord ??= app(DiscordService::class);
    }
}
