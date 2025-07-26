<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Class DatabaseSeeder
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Native commands are now handled via individual migrations
        // $this->call([
        //     NativeCommandSeeder::class,
        // ]);
    }
}
