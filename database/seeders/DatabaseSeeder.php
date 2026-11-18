<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/** Seeds the homepage content. The CMS admin login is seeded from the nonstop_cms repo. */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ContentSeeder::class);
        $this->call(ContactPageSeeder::class); // additive: adds missing contact-page content, never overwrites
        $this->call(HomeExtrasSeeder::class); // additive: fills new homepage fields that are still empty
        $this->call(SeoSeeder::class); // additive: starter SEO tags per page, never overwrites
    }
}
