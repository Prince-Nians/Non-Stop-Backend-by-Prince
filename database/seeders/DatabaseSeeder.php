<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/** Seeds the homepage content. The CMS admin login is seeded from the nonstop_cms repo. */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ContentSeeder::class);
    }
}
