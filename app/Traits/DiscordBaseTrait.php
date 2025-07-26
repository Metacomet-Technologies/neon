<?php

declare(strict_types=1);

namespace App\Traits;

use App\Services\Discord\Discord;

/**
 * Base trait for Discord functionality.
 * Provides shared Discord SDK instance management.
 */
trait DiscordBaseTrait
{
    private ?Discord $discord = null;

    /**
     * Get Discord SDK instance.
     */
    protected function getDiscord(): Discord
    {
        if (! $this->discord) {
            $this->discord = new Discord;
        }

        return $this->discord;
    }
}
