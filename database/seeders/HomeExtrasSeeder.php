<?php

namespace Database\Seeders;

use App\Models\PageSection;
use Illuminate\Database\Seeder;

/** Small additions to the homepage content. Additive: only fills what is still empty, never overwrites edits. */
class HomeExtrasSeeder extends Seeder
{
    public function run(): void
    {
        // "Learn More" button under Our Products.
        PageSection::where('key', 'products')->whereNull('button_label')->update(['button_label' => 'Learn More', 'button_url' => '#about']);
    }
}
