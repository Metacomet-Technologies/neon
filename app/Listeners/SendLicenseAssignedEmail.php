<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\LicenseAssigned;
use App\Mail\LicenseAssignedMail;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class SendLicenseAssignedEmail
{
    /**
     * Handle the event.
     */
    public function handle(LicenseAssigned $event): void
    {
        try {
            Mail::to($event->license->user->email)->send(
                new LicenseAssignedMail($event->license, $event->guild)
            );
        } catch (Exception $e) {
            // Log error but don't fail the operation
            Log::error('Failed to send license assigned email', [
                'license_id' => $event->license->id,
                'guild_id' => $event->guild->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
