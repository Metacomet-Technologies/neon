<?php

declare(strict_types=1);

namespace App\Exceptions\License;

use Exception;

class GuildAlreadyHasLicenseException extends Exception
{
    public function __construct(string $guildId)
    {
        $message = "Guild {$guildId} already has an active license assigned.";
        parent::__construct($message);
    }
}
