<?php

namespace Database\Seeders;

use App\Models\NativeCommand;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NativeCommandSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $commands = [
            [
                'slug' => 'archive-channel',
                'description' => 'Archives or unarchives a channel.',
                'class' => \App\Jobs\ProcessArchiveChannelJob::class,
                'usage' => 'Usage: !archive-channel <channel-id> <true|false>',
                'example' => 'Example: !archive-channel 123456789012345678 true',
                'is_active' => false,
            ],
            [
                'slug' => 'assign-channel',
                'description' => 'Assigns a channel to a category.',
                'class' => \App\Jobs\ProcessAssignChannelJob::class,
                'usage' => 'Usage: !assign-channel <channel-id|channel-name> <category-id|category-name>',
                'example' => 'Example: !assign-channel 123456789012345678 987654321098765432',
                'is_active' => true,
            ],
            [
                'slug' => 'assign-role',
                'description' => 'Assigns a role to one or more users.',
                'class' => \App\Jobs\ProcessAssignRoleJob::class,
                'usage' => 'Usage: !assign-role <role-name> <@user1> <@user2> ...',
                'example' => 'Example: !assign-role VIP 987654321098765432',
                'is_active' => true,
            ],
            [
                'slug' => 'ban',
                'description' => 'Bans a user from the server.',
                'class' => \App\Jobs\ProcessBanUserJob::class,
                'usage' => 'Usage: !ban <user-id>',
                'example' => 'Example: !ban 123456789012345678',
                'is_active' => true,
            ],
            [
                'slug' => 'delete-category',
                'description' => 'Deletes a category.',
                'class' => \App\Jobs\ProcessDeleteCategoryJob::class,
                'usage' => 'Usage: !delete-category <category-id>',
                'example' => 'Example: !delete-category 123456789012345678',
                'is_active' => true,
            ],
            [
                'slug' => 'delete-channel',
                'description' => 'Deletes a channel.',
                'class' => \App\Jobs\ProcessDeleteChannelJob::class,
                'usage' => 'Usage: !delete-channel <channel-id|channel-name>',
                'example' => 'Example: !delete-channel 123456789012345678 or !delete-channel #general',
                'is_active' => true,
            ],
            [
                'slug' => 'delete-event',
                'description' => 'Deletes a scheduled event.',
                'class' => \App\Jobs\ProcessDeleteEventJob::class,
                'usage' => 'Usage: !delete-event <event-id>',
                'example' => 'Example: !delete-event 123456789012345678',
                'is_active' => true,
            ],
            [
                'slug' => 'delete-role',
                'description' => 'Deletes a role.',
                'class' => \App\Jobs\ProcessDeleteRoleJob::class,
                'usage' => 'Usage: !delete-role <role-name>',
                'example' => 'Example: !delete-role VIP',
                'is_active' => true,
            ],
            [
                'slug' => 'display-boost',
                'description' => 'Displays Nitro boost bar status.',
                'class' => \App\Jobs\ProcessDisplayBoostJob::class,
                'usage' => 'Usage: !display-boost <true|false>',
                'example' => 'Example: !display-boost true',
                'is_active' => true,
            ],
            [
                'slug' => 'edit-channel-autohide',
                'description' => 'Edits channel autohide settings.',
                'class' => \App\Jobs\ProcessEditChannelAutohideJob::class,
                'usage' => 'Usage: !edit-channel-autohide <channel-id> <minutes>',
                'example' => 'Example: !edit-channel-autohide 123456789012345678 1440',
                'is_active' => true,
            ],
            [
                'slug' => 'edit-channel-name',
                'description' => 'Edits a channel name.',
                'class' => \App\Jobs\ProcessEditChannelNameJob::class,
                'usage' => 'Usage: !edit-channel-name <channel-id> <new-name>',
                'example' => 'Example: !edit-channel-name 123456789012345678 new-channel-name',
                'is_active' => true,
            ],
            [
                'slug' => 'edit-channel-nsfw',
                'description' => 'Edits a channel age-rating or "not suitable for work" NSFW.',
                'class' => \App\Jobs\ProcessEditChannelNSFWJob::class,
                'usage' => 'Usage: !edit-channel-nsfw <channel-id> <true|false>',
                'example' => 'Example: !edit-channel-nsfw 123456789012345678 true',
                'is_active' => true,
            ],
            [
                'slug' => 'edit-channel-slowmode',
                'description' => 'Edits a channel to have slowmode.',
                'class' => \App\Jobs\ProcessEditChannelSlowmodeJob::class,
                'usage' => 'Usage: !edit-channel-slowmode <channel-id> <seconds>',
                'example' => 'Example: !edit-channel-slowmode 123456789012345678 10',
                'is_active' => true,
            ],
            [
                'slug' => 'edit-channel-topic',
                'description' => 'Edits a channel topic.',
                'class' => \App\Jobs\ProcessEditChannelTopicJob::class,
                'usage' => 'Usage: !edit-channel-topic <channel-id> <new-topic>',
                'example' => 'Example: !edit-channel-topic 123456789012345678 New topic description',
                'is_active' => true,
            ],
            [
                'slug' => 'create-event',
                'description' => 'Creates a new scheduled event.',
                'class' => \App\Jobs\ProcessNewEventJob::class,
                'usage' => 'Usage: !create-event <event-topic> | <start-date> | <start-time> | <event-frequency> | <location> | <description> | [cover-image-url]',
                'example' => 'Example: !create-event "Weekly Meeting" | 2025-02-10 | 14:00 | weekly | #general | "Join us for our weekly team meeting" | https://example.com/cover.jpg',
                'is_active' => true,
            ],
            [
                'slug' => 'kick',
                'description' => 'Kicks a user from the server.',
                'class' => \App\Jobs\ProcessKickUserJob::class,
                'usage' => 'Usage: !kick <user-id>',
                'example' => 'Example: !kick 123456789012345678',
                'is_active' => true,
            ],
            [
                'slug' => 'lock-channel',
                'description' => 'Locks or unlocks a text channel.',
                'class' => \App\Jobs\ProcessLockChannelJob::class,
                'usage' => 'Usage: !lock-channel <channel-id> <true|false>',
                'example' => 'Example: !lock-channel 123456789012345678 true',
                'is_active' => true,
            ],
            [
                'slug' => 'move-user',
                'description' => 'Moves a user to a different voice channel.',
                'class' => \App\Jobs\ProcessMoveUserJob::class,
                'usage' => 'Usage: !move-user <@userID | userID> <channelID>',
                'example' => 'Example: !move-user 123456789012345678 123456789012345678',
                'is_active' => true,
            ],
            [
                'slug' => 'mute',
                'description' => 'Mutes a user in the server.',
                'class' => \App\Jobs\ProcessMuteUserJob::class,
                'usage' => 'Usage: !mute <user-id>',
                'example' => 'Example: !mute 123456789012345678',
                'is_active' => true,
            ],
            [
                'slug' => 'new-category',
                'description' => 'Creates a new category in the server.',
                'class' => \App\Jobs\ProcessNewCategoryJob::class,
                'usage' => 'Usage: !new-category <category-name>',
                'example' => 'Example: !new-category test-category',
                'is_active' => true,
            ],
            [
                'slug' => 'new-channel',
                'description' => 'Creates a new text or voice channel.',
                'class' => \App\Jobs\ProcessNewChannelJob::class,
                'usage' => 'Usage: !new-channel <channel-name> <channel-type> [category-id] [channel-topic]',
                'example' => 'Example: !new-channel test-channel text 123456789012345678 "A fun chat for everyone!"',
                'is_active' => true,
            ],
            [
                'slug' => 'new-role',
                'description' => 'Creates a new role with optional color and hoist settings.',
                'class' => \App\Jobs\ProcessNewRoleJob::class,
                'usage' => 'Usage: !new-role <role-name> [color] [hoist]',
                'example' => 'Example: !new-role VIP #3498db yes',
                'is_active' => true,
            ],
            [
                'slug' => 'pin',
                'description' => 'Pins a specific message or the last message in the channel.',
                'class' => \App\Jobs\ProcessPinMessagesJob::class,
                'usage' => 'Usage: !pin <message-id> or !pin this',
                'example' => 'Example: !pin 123456789012345678 or !pin this',
                'is_active' => true,
            ],
            [
                'slug' => 'purge',
                'description' => 'Deletes a specified number of messages from a channel.',
                'class' => \App\Jobs\ProcessPurgeMessagesJob::class,
                'usage' => 'Usage: !purge #channel <number>',
                'example' => 'Example: !purge #general 100',
                'is_active' => true,
            ],
            [
                'slug' => 'remove-role',
                'description' => 'Removes a role from one or more users.',
                'class' => \App\Jobs\ProcessRemoveRoleJob::class,
                'usage' => 'Usage: !remove-role <role-name> <@user1> <@user2> ...',
                'example' => 'Example: !remove-role VIP 123456789012345678 123456789012345678',
                'is_active' => true,
            ],
            [
                'slug' => 'set-inactive',
                'description' => 'Sets a timeout for marking a channel as inactive.',
                'class' => \App\Jobs\ProcessSetInactiveJob::class,
                'usage' => 'Usage: !set-inactive <channel-name|channel-id> <timeout>',
                'example' => 'Example: !set-inactive general-voice 300',
                'is_active' => true,
            ],
            [
                'slug' => 'unban',
                'description' => 'Unbans a user from the server.',
                'class' => \App\Jobs\ProcessUnbanUserJob::class,
                'usage' => 'Usage: !unban <user-id>',
                'example' => 'Example: !unban 1335401202648748064',
                'is_active' => true,
            ],
            [
                'slug' => 'unmute',
                'description' => 'Unmutes a user in the server.',
                'class' => \App\Jobs\ProcessUnmuteUserJob::class,
                'usage' => 'Usage: !unmute <user-id>',
                'example' => 'Example: !unmute 123456789012345678',
                'is_active' => true,
            ],
            [
                'slug' => 'unpin',
                'description' => 'Unpins a specified message.',
                'class' => \App\Jobs\ProcessUnpinMessagesJob::class,
                'usage' => 'Usage: !unpin <message-id>',
                'example' => 'Example: !unpin 123456789012345678',
                'is_active' => true,
            ],
            [
                'slug' => 'lock-voice',
                'description' => 'Locks or unlocks a voice channel.',
                'class' => \App\Jobs\ProcessLockVoiceChannelJob::class,
                'usage' => 'Usage: !lock-voice <channel-id> <true|false>',
                'example' => 'Example: !lock-voice 123456789012345678 true',
                'is_active' => true,
            ],
            [
                'slug' => 'disconnect',
                'description' => 'Disconnects one or more users from a voice channel.',
                'class' => \App\Jobs\ProcessDisconnectUserJob::class,
                'usage' => 'Usage: !disconnect <@user1> [@user2] ...',
                'example' => 'Example: !disconnect @User1 @User2',
                'is_active' => true,
            ],
            [
                'slug' => 'poll',
                'description' => 'Creates a poll with multiple voting options.',
                'class' => \App\Jobs\ProcessCreatePollJob::class,
                'usage' => 'Usage: !poll "Question" "Option 1" "Option 2" "Option 3"',
                'example' => 'Example: !poll "What should we play?" "Minecraft" "Valorant" "Overwatch"',
                'is_active' => true,
            ],
            [
                'slug' => 'help',
                'description' => 'Displays a list of available commands.',
                'class' => \App\Jobs\ProcessHelpCommandJob::class,
                'usage' => 'Usage: !help',
                'example' => 'Example: !help',
                'is_active' => true,
            ],
            [
                'slug' => 'support',
                'description' => 'Sends a support message to a designated support server.',
                'class' => \App\Jobs\ProcessSupportCommandJob::class,
                'usage' => 'Usage: !support <message>',
                'example' => 'Example: !support I need help with my role.',
                'is_active' => false,
            ],

        ];

        foreach ($commands as $command) {
            NativeCommand::updateOrCreate(['slug' => $command['slug']], $command);
        }
    }
}
