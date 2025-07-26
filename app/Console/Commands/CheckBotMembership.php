<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\CheckAllGuildsBotMembership;
use App\Jobs\CheckGuildBotMembership;
use App\Models\Guild;
use Illuminate\Console\Command;

final class CheckBotMembership extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bot:check-membership {guild? : The guild ID to check (optional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check bot membership status for guilds';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $guildId = $this->argument('guild');

        if ($guildId) {
            $guild = Guild::find($guildId);

            if (! $guild) {
                $this->error("Guild with ID {$guildId} not found.");

                return 1;
            }

            $this->info("Checking bot membership for guild: {$guild->name}");
            CheckGuildBotMembership::dispatchSync($guild);

            $guild->refresh();
            $this->info('Bot member status: ' . ($guild->is_bot_member ? 'Yes' : 'No'));

            if ($guild->bot_joined_at) {
                $this->info("Bot joined at: {$guild->bot_joined_at}");
            }

            if ($guild->bot_left_at) {
                $this->info("Bot left at: {$guild->bot_left_at}");
            }
        } else {
            $this->info('Checking bot membership for all guilds...');
            CheckAllGuildsBotMembership::dispatchSync();
            $this->info('Bot membership check completed.');
        }

        return 0;
    }
}
