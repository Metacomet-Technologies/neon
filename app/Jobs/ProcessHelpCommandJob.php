<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\SendMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ProcessHelpCommandJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $discordUserId,
        public string $channelId,
        public string $guildId,
        public string $messageContent,
    ) {}

    public function handle(): void
    {
        if (! Schema::hasTable('native_commands')) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Commands database is missing. Please contact an administrator.',
            ]);

            return;
        }

        $commands = DB::table('native_commands')
            ->where('is_active', true)
            ->select('slug', 'description', 'usage', 'example')
            ->get();

        if ($commands->isEmpty()) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ No commands are currently available.',
            ]);

            return;
        }

        $helpChunks = [];
        $currentMessage = "**📜 Available Commands:**\n\n";

        foreach ($commands as $command) {
            $description = $command->description ?? '*No description available.*';
            $usage = $command->usage ?? '*No usage info.*';
            $example = $command->example ?? '*No example provided.*';

            $commandText = '**`!' . $command->slug . '`** - ' . $description . "\n";
            // $commandText .= "*Usage:* `" . $usage . "`\n";
            // $commandText .= "*Example:* `" . $example . "`\n\n";

            if (strlen($currentMessage) + strlen($commandText) > 1900) {
                $helpChunks[] = $currentMessage;
                $currentMessage = '';
            }

            $currentMessage .= $commandText;
        }

        if (! empty($currentMessage)) {
            $helpChunks[] = $currentMessage;
        }

        foreach ($helpChunks as $chunk) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => $chunk,
            ]);
        }
    }
}
