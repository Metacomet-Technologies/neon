<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Jobs\NativeCommand\Base\ProcessBaseJob;
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

        // Validate days is numeric and in range
        if (! is_numeric($days)) {
            $this->sendErrorMessage('Days must be a number.');
            throw new Exception('Invalid days value.', 400);
        }

        $validatedDays = (int) $days;
        if ($validatedDays < 1 || $validatedDays > 30) {
            $this->sendErrorMessage('Days must be between 1 and 30.');
            throw new Exception('Days out of range.', 400);
        }

        $result = $this->getDiscord()->pruneInactiveMembers($this->guildId, $validatedDays);

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
