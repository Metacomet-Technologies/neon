<?php

declare (strict_types = 1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;

class CashierServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Cashier::calculateTaxes();
    }
}
