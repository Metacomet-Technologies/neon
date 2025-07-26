<?php

declare(strict_types=1);

namespace App\Services\Discord\Resources;

use App\Services\Discord\Discord;
use Illuminate\Support\Collection;

/**
 * Channel builder for expressive Discord API operations.
 *
 * Usage:
 * $channels = $guild->channels()->get();
 * $textChannels = $guild->channels()->text()->get();
 * $voiceChannels = $guild->channels()->voice()->get();
 */
final class ChannelBuilder
{
    private ?int $type = null;

    public function __construct(
        private Discord $discord,
        private string $guildId
    ) {}

    /**
     * Get all channels.
     */
    public function get(): Collection
    {
        $channels = $this->discord->get("/guilds/{$this->guildId}/channels");
        $collection = collect($channels);

        if ($this->type !== null) {
            return $collection->where('type', $this->type);
        }

        return $collection;
    }

    /**
     * Filter to text channels only.
     */
    public function text(): self
    {
        $this->type = 0; // GUILD_TEXT

        return $this;
    }

    /**
     * Filter to voice channels only.
     */
    public function voice(): self
    {
        $this->type = 2; // GUILD_VOICE

        return $this;
    }

    /**
     * Filter to category channels only.
     */
    public function categories(): self
    {
        $this->type = 4; // GUILD_CATEGORY

        return $this;
    }

    /**
     * Find channel by name.
     */
    public function findByName(string $name): ?array
    {
        return $this->get()->first(fn ($channel) => $channel['name'] === $name);
    }

    /**
     * Create a new channel.
     */
    public function create(array $data): Channel
    {
        $channelData = $this->discord->post("/guilds/{$this->guildId}/channels", $data);

        return new Channel($this->discord, $channelData['id']);
    }
}
