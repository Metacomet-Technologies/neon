<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update all existing native commands to use the new namespace
        DB::table('native_commands')
            ->where('class', 'like', 'App\Jobs\Process%')
            ->where('class', 'not like', 'App\Jobs\NativeCommand\%')
            ->update([
                'class' => DB::raw("REPLACE(class, 'App\\\\Jobs\\\\', 'App\\\\Jobs\\\\NativeCommand\\\\')"),
            ]);

        // Fix specific mismatched class names
        $fixes = [
            'App\Jobs\NativeCommand\ProcessShowHelpJob' => 'App\Jobs\NativeCommand\ProcessHelpCommandJob',
            'App\Jobs\NativeCommand\ProcessChangeRoleColorJob' => 'App\Jobs\NativeCommand\ProcessColorJob',
            'App\Jobs\NativeCommand\ProcessCreateEventJob' => 'App\Jobs\NativeCommand\ProcessNewEventJob',
            'App\Jobs\NativeCommand\ProcessShowSupportJob' => 'App\Jobs\NativeCommand\ProcessSupportCommandJob',
            'App\Jobs\NativeCommand\ProcessSetNicknameJob' => 'App\Jobs\NativeCommand\ProcessUserNicknameJob',
            'App\Jobs\NativeCommand\ProcessPinMessageJob' => 'App\Jobs\NativeCommand\ProcessPinMessagesJob',
            'App\Jobs\NativeCommand\ProcessUnpinMessageJob' => 'App\Jobs\NativeCommand\ProcessUnpinMessagesJob',
            'App\Jobs\NativeCommand\ProcessEditChannelNsfwJob' => 'App\Jobs\NativeCommand\ProcessEditChannelNSFWJob',
        ];

        foreach ($fixes as $oldClass => $newClass) {
            DB::table('native_commands')
                ->where('class', $oldClass)
                ->update(['class' => $newClass]);
        }
    }

    public function down(): void
    {
        // Revert the namespace changes
        DB::table('native_commands')
            ->where('class', 'like', 'App\Jobs\NativeCommand\Process%')
            ->update([
                'class' => DB::raw("REPLACE(class, 'App\\\\Jobs\\\\NativeCommand\\\\', 'App\\\\Jobs\\\\')"),
            ]);
    }
};
