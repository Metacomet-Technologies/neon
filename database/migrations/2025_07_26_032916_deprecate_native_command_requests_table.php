<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add a comment to mark the table as deprecated
        DB::statement('ALTER TABLE native_command_requests COMMENT = "DEPRECATED: Use failed_jobs for queue failures and command_usage_metrics for analytics"');

        // Optionally, you could rename the table to indicate it's deprecated
        // Schema::rename('native_command_requests', 'native_command_requests_deprecated');
    }

    public function down(): void
    {
        // Remove the deprecation comment
        DB::statement('ALTER TABLE native_command_requests COMMENT = ""');

        // If you renamed the table, you'd restore it here:
        // Schema::rename('native_command_requests_deprecated', 'native_command_requests');
    }
};
