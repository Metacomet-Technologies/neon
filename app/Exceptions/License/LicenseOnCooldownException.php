<?php

declare(strict_types=1);

namespace App\Exceptions\License;

use Exception;

final class LicenseOnCooldownException extends Exception
{
    public function __construct(int $daysRemaining)
    {
        $message = "License is on cooldown. Cannot reassign for {$daysRemaining} more days.";
        parent::__construct($message);
    }
}
