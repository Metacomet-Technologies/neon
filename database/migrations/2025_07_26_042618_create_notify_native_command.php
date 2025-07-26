<?php

declare(strict_types=1);

use App\Models\NativeCommand;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NativeCommand::updateOrCreate(['slug' => 'notify'], [
            'slug' => 'notify',
            'description' => 'Sends a notification message to specified users or roles.',
            'class' => \App\Jobs\ProcessNotifyMessageJob::class,
            'usage' => 'Usage: !notify <@user|@role> <message>',
            'example' => 'Example: !notify @VIP "Meeting starts in 5 minutes!"',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        NativeCommand::where('slug', 'notify')->delete();
    }
};
