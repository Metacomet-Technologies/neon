<?php

declare(strict_types=1);

namespace App\Exceptions\License;

use Exception;

final class LicenseNotAssignedException extends Exception
{
    public function __construct()
    {
        $message = 'License is not currently assigned to any guild.';
        parent::__construct($message);
    }
}
