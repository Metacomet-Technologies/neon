<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use Exception;

final class ProcessPruneInactiveMembersJob extends ProcessBaseJob
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
        $this->requireMemberPermission();

        $days = $this->extractDaysFromMessage($this->messageContent);
        if (! $days) {
            $this->sendUsageAndExample();
            throw new Exception('Number of days not specified.', 400);
        }

        $validatedDays = $this->validateNumericRange($days, 1, 30, 'Days');

        $result = $this->discord->pruneInactiveMembers($this->guildId, $validatedDays);

        if (! $result) {
            $this->sendApiError('prune inactive members');
            throw new Exception('Failed to prune inactive members.', 500);
        }

        $prunedCount = $result['pruned'] ?? 0;
        $this->sendSuccessMessage(
            'Members Pruned',
            "Successfully pruned {$prunedCount} inactive members from the server."
        );
    }

    private function extractDaysFromMessage(string $content): ?string
    {
        $parts = explode(' ', trim($content));

        return $parts[1] ?? null;
    }
}
