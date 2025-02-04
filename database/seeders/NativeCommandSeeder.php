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
                'help' => 'Usage: !archive-channel <channel-id> <true|false>',
                'example' => 'Example: !archive-channel 123456789012345678 true',
                'is_active' => true,
            ],
            [
                'slug' => 'assign-channel',
                'description' => 'Assigns a channel to a category.',
                'class' => \App\Jobs\ProcessAssignChannelJob::class,
                'help' => 'Usage: !assign-channel <channel-id|channel-name> <category-id|category-name>',
                'example' => 'Example: !assign-channel 123456789012345678 987654321098765432',
                'is_active' => true,
            ],
            [
                'slug' => 'assign-role',
                'description' => 'Assigns a role to one or more users.',
                'class' => \App\Jobs\ProcessAssignRoleJob::class,
                'help' => 'Usage: !assign-role <role-name> <@user1> <@user2> ...',
                'example' => 'Example: !assign-role VIP 987654321098765432',
                'is_active' => true,
            ],
            [
                'slug' => 'ban',
                'description' => 'Bans a user from the server.',
                'class' => \App\Jobs\ProcessBanUserJob::class,
                'help' => 'Usage: !ban <user-id>',
                'example' => 'Example: !ban 123456789012345678',
                'is_active' => true,
            ],
            [
                'slug' => 'delete-category',
                'description' => 'Deletes a category.',
                'class' => \App\Jobs\ProcessDeleteCategoryJob::class,
                'help' => 'Usage: !delete-category <category-id>',
                'example' => 'Example: !delete-category 123456789012345678',
                'is_active' => true,
            ],
            [
                'slug' => 'delete-channel',
                'description' => 'Deletes a channel.',
                'class' => \App\Jobs\ProcessDeleteChannelJob::class,
                'help' => 'Usage: !delete-channel <channel-id|channel-name>',
                'example' => 'Example: !delete-channel 123456789012345678 or !delete-channel #general',
                'is_active' => true,
            ],
            [
                'slug' => 'delete-event',
                'description' => 'Deletes a scheduled event.',
                'class' => \App\Jobs\ProcessDeleteEventJob::class,
                'help' => 'Usage: !delete-event <event-id>',
                'example' => 'Example: !delete-event 123456789012345678',
                'is_active' => true,
            ],
            [
                'slug' => 'delete-role',
                'description' => 'Deletes a role.',
                'class' => \App\Jobs\ProcessDeleteRoleJob::class,
                'help' => 'Usage: !delete-role <role-name>',
                'example' => 'Example: !delete-role VIP',
                'is_active' => true,
            ],
            [
                'slug' => 'edit-channel-autohide',
                'description' => 'Edits channel autohide settings.',
                'class' => \App\Jobs\ProcessEditChannelAutohideJob::class,
                'help' => 'Usage: !edit-channel-autohide <channel-id> <minutes>',
                'example' => 'Example: !edit-channel-autohide 123456789012345678 1440',
                'is_active' => true,
            ],
            [
                'slug' => 'edit-channel-topic',
                'description' => 'Edits a channel topic.',
                'class' => \App\Jobs\ProcessEditChannelTopicJob::class,
                'help' => 'Usage: !edit-channel-topic <channel-id> <new-topic>',
                'example' => 'Example: !edit-channel-topic 123456789012345678 New topic description',
                'is_active' => true,
            ],
            [
                'slug' => 'create-event',
                'description' => 'Creates a new scheduled event.',
                'class' => \App\Jobs\ProcessNewEventJob::class,
                'help' => 'Usage: !create-event <event-topic> | <start-date> | <start-time> | <event-frequency> | <location> | <description> | [cover-image-url]',
                'example' => 'Example: !create-event "Weekly Meeting" | 2025-02-10 | 14:00 | weekly | #general | "Join us for our weekly team meeting" | https://example.com/cover.jpg',
                'is_active' => true,
            ],
            [
                'slug' => 'new-role',
                'description' => 'Creates a new role with optional color and hoist settings.',
                'class' => \App\Jobs\ProcessNewRoleJob::class,
                'help' => 'Usage: !new-role <role-name> [color] [hoist]',
                'example' => 'Example: !new-role VIP #3498db yes',
                'is_active' => true,
            ],
            [
                'slug' => 'pin',
                'description' => 'Pins a specific message or the last message in the channel.',
                'class' => \App\Jobs\ProcessPinMessagesJob::class,
                'help' => 'Usage: !pin <message-id> or !pin this',
                'example' => 'Example: !pin 123456789012345678 or !pin this',
                'is_active' => true,
            ],
            [
                'slug' => 'purge',
                'description' => 'Deletes a specified number of messages from a channel.',
                'class' => \App\Jobs\ProcessPurgeMessagesJob::class,
                'help' => 'Usage: !purge #channel <number>',
                'example' => 'Example: !purge #general 100',
                'is_active' => true,
            ],
            [
                'slug' => 'remove-role',
                'description' => 'Removes a role from one or more users.',
                'class' => \App\Jobs\ProcessRemoveRoleJob::class,
                'help' => 'Usage: !remove-role <role-name> <@user1> <@user2> ...',
                'example' => 'Example: !remove-role VIP 123456789012345678 123456789012345678',
                'is_active' => true,
            ],
            [
                'slug' => 'set-inactive',
                'description' => 'Sets a timeout for marking a channel as inactive.',
                'class' => \App\Jobs\ProcessSetInactiveJob::class,
                'help' => 'Usage: !set-inactive <channel-name|channel-id> <timeout>',
                'example' => 'Example: !set-inactive general-voice 300',
                'is_active' => true,
            ],
            [
                'slug' => 'unban',
                'description' => 'Unbans a user from the server.',
                'class' => \App\Jobs\ProcessUnbanUserJob::class,
                'help' => 'Usage: !unban <user-id>',
                'example' => 'Example: !unban 1335401202648748064',
                'is_active' => true,
            ],
            [
                'slug' => 'unmute',
                'description' => 'Unmutes a user in the server.',
                'class' => \App\Jobs\ProcessUnmuteUserJob::class,
                'help' => 'Usage: !unmute <user-id>',
                'example' => 'Example: !unmute 123456789012345678',
                'is_active' => true,
            ],
            [
                'slug' => 'unpin',
                'description' => 'Unpins a specified message.',
                'class' => \App\Jobs\ProcessUnpinMessagesJob::class,
                'help' => 'Usage: !unpin <message-id>',
                'example' => 'Example: !unpin 123456789012345678',
                'is_active' => true,
            ],

        ];

        foreach ($commands as $command) {
            NativeCommand::updateOrCreate(['slug' => $command['slug']], $command);
        }
    }
}
