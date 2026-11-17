<?php

namespace Database\Seeders;

use App\Models\{PageSeo, SiteSetting};
use Illuminate\Database\Seeder;

/** Starter search/social tags for every page. Additive: only creates a page's row if it is missing, never overwrites edits. */
class SeoSeeder extends Seeder
{
    public function run(): void
    {
        $s = SiteSetting::first();
        $pages = [
            'home' => [
                'meta_title' => $s?->seo_title ?: 'NON STOP — Stay Nonstop | Energy Drink',
                'meta_description' => $s?->seo_description ?: 'NON STOP energy drink — explore our flavours, find where to buy, partner with us, and read the latest events and blogs.',
                'keywords' => 'energy drink, non stop, nonstop energy, buy energy drink, energy drink flavours',
            ],
            'contact' => [
                'meta_title' => 'Contact Us — NON STOP',
                'meta_description' => 'Get in touch with the NON STOP team. Send us a message, find our email, phone number and address, and read our frequently asked questions.',
                'keywords' => 'contact non stop, non stop energy drink contact, customer support',
            ],
        ];
        foreach ($pages as $page => $data) {
            PageSeo::firstOrCreate(['page' => $page], $data);
        }
    }
}
