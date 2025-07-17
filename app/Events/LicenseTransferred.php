<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Guild;
use App\Models\License;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LicenseTransferred
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly License $license,
        public readonly Guild $fromGuild,
        public readonly Guild $toGuild
    ) {
        //
    }
}
