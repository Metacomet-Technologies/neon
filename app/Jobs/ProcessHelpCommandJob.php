<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\SendMessage;
use App\Jobs\NativeCommand\ProcessBaseJob;
use App\Models\NativeCommandRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ProcessHelpCommandJob extends ProcessBaseJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public NativeCommandRequest $nativeCommandRequest)
    {
        parent::__construct($nativeCommandRequest);
    }

    public function handle(): void
    {
        if (! Schema::hasTable('native_commands')) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Commands database is missing. Please contact an administrator.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'Commands database is missing.',
                statusCode: 500,
            );

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
            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'No commands are currently available.',
                statusCode: 404,
            );

            return;
        }

        $helpChunks = [];
        $currentMessage = "**For Syntax and Example type a command with no parameters.\n\nAvailable Commands:**\n\n";

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
        $this->updateNativeCommandRequestComplete();
    }
}
