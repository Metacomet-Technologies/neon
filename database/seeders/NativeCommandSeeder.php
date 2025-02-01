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
                'slug' => 'assign-channel',
                'class' => \App\Jobs\ProcessAssignChannelJob::class,
                'is_active' => true,
            ],
            [
                'slug' => 'assign-role',
                'class' => \App\Jobs\ProcessAssignRoleJob::class,
                'is_active' => true,
            ],
            [
                'slug' => 'delete-category',
                'class' => \App\Jobs\ProcessDeleteCategoryJob::class,
                'is_active' => true,
            ],
            [
                'slug' => 'delete-channel',
                'class' => \App\Jobs\ProcessDeleteChannelJob::class,
                'is_active' => true,
            ],
            [
                'slug' => 'delete-role',
                'class' => \App\Jobs\ProcessDeleteRoleJob::class,
                'is_active' => true,
            ],
            [
                'slug' => 'edit-channel',
                'class' => \App\Jobs\ProcessEditChannelJob::class,
                'is_active' => true,
            ],
            [
                'slug' => 'edit-channel-name',
                'class' => \App\Jobs\ProcessEditChannelNameJob::class,
                'is_active' => true,
            ],
            [
                'slug' => 'edit-channel-nsfw',
                'class' => \App\Jobs\ProcessEditChannelNSFWJob::class,
                'is_active' => true,
            ],
            [
                'slug' => 'edit-channel-slowmode',
                'class' => \App\Jobs\ProcessEditChannelSlowmodeJob::class,
                'is_active' => true,
            ],
            [
                'slug' => 'edit-channel-topic',
                'class' => \App\Jobs\ProcessEditChannelTopicJob::class,
                'is_active' => true,
            ],
            [
                'slug' => 'lock-channel',
                'class' => \App\Jobs\ProcessLockChannelJob::class,
                'is_active' => true,
            ],
            [
                'slug' => 'new-category',
                'class' => \App\Jobs\ProcessNewCategoryJob::class,
                'is_active' => true,
            ],
            [
                'slug' => 'new-channel',
                'class' => \App\Jobs\ProcessNewChannelJob::class,
                'is_active' => true,
            ],
            [
                'slug' => 'create-event',
                'class' => \App\Jobs\ProcessNewEventJob::class,
                'is_active' => true,
            ],
            [
                'slug' => 'new-role',
                'class' => \App\Jobs\ProcessNewRoleJob::class,
                'is_active' => true,
            ],
            [
                'slug' => 'remove-role',
                'class' => \App\Jobs\ProcessRemoveRoleJob::class,
                'is_active' => true,
            ],
        ];

        foreach ($commands as $command) {
            NativeCommand::create($command);
        }
    }
}
