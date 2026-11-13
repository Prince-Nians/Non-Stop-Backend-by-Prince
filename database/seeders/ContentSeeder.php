<?php

namespace Database\Seeders;

use App\Models\{AboutPoint, BlogPost, Event, NavLink, PageSection, Product, Shop, SiteSetting, SocialPhoto};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Loads the current Figma homepage content. Images are read from SEED_ASSETS_PATH
 * (the frontend's public/figma folder), converted to WebP and stored on the public disk.
 */
class ContentSeeder extends Seeder
{
    private string $src;

    public function run(): void
    {
        $this->src = rtrim(env('SEED_ASSETS_PATH', base_path('../nonstop/public/figma')), '/');
        if (! is_dir($this->src)) {
            $this->command?->warn("Seed assets not found at {$this->src}; images will be empty.");
        }

        $lorem = 'consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium.';
        $short = 'Lorem ipsum dolor sit amet consectetur. A eleifend turpis sed orci est amet. Urna nulla ut nunc in fusce a';

        SiteSetting::updateOrCreate(['id' => 1], [
            'site_name' => 'NON STOP',
            'logo' => $this->img('logo'),
            'phone' => '+91 00000 00000',
            'email' => 'info@nonstop.example',
            'copyright_text' => '© 2026 NON Stop. All Rights Reserved',
            'facebook_url' => '#', 'youtube_url' => '#', 'linkedin_url' => '#', 'twitter_url' => '#',
            'contact_notify_email' => 'info@nonstop.example',
            'partner_notify_email' => 'info@nonstop.example',
            'seo_title' => 'NON STOP — Stay Nonstop',
        ]);

        foreach ([
            ['JioMart', 'jiomart', 'https://www.jiomart.com'],
            ['Zepto', 'zepto', 'https://www.zeptonow.com'],
            ['Blinkit', 'blinkit', 'https://blinkit.com'],
        ] as $i => [$name, $slug, $url]) {
            Shop::updateOrCreate(['slug' => $slug], ['name' => $name, 'url' => $url, 'logo' => $this->img($slug), 'sort_order' => $i]);
        }

        NavLink::query()->delete();
        $rows = [
            'header' => [['HOME', '#top'], ['ABOUT US', '#about'], ['PRODUCTS', '#products'], ['EVENTS', '#events'], ['BLOGS', '#blogs'], ['PARTNERSHIP', '#partner'], ['CONTACT US', '#contact']],
            'footer' => [['Home', '#top'], ['Blogs', '#blogs'], ['About us', '#about'], ['Partnerships', '#partner'], ['Products', '#products'], ['contact us', '#contact'], ['Events', '#events']],
            'legal' => [['Disclaimer', '#top'], ['Privacy Policy', '#top'], ['Site Map', '#top']],
        ];
        foreach ($rows as $loc => $links) {
            foreach ($links as $i => [$label, $url]) {
                NavLink::create(['location' => $loc, 'label' => $label, 'url' => $url, 'sort_order' => $i]);
            }
        }

        $sections = [
            'hero' => ['title' => 'Stay Nonstop', 'image' => $this->img('hero-bg'), 'image_secondary' => $this->img('hero-title', false)],
            'products' => ['title' => 'Our Products', 'description' => $lorem],
            'partner' => [
                'title' => 'BECOME A PARTNER', 'subtitle' => 'PARTNER PROGRAM',
                'description' => 'Lorem ipsum dolor sit amet consectetur. A eleifend turpis sed orci est amet Urna nulla ut nunc in fusce a. Lacinia nulla sapien malesuada',
                'button_label' => 'Know More', 'button_url' => '#partner',
                'image' => $this->img('products-bg'), 'extra' => ['buy_now_title' => 'BUY NOW'],
            ],
            'about' => ['title' => 'About Us', 'description' => $lorem, 'button_label' => 'Become a Partner', 'button_url' => '#partner', 'image' => $this->img('about-can')],
            'social' => [
                'title' => 'Social Media Handle', 'description' => $lorem, 'button_label' => 'Read More', 'button_url' => '#social',
                'image' => $this->img('social-bg'),
                'extra' => ['copy' => 'Lorem ipsum dolor sit amet consectetur. A eleifend turpis sed orci est amet. Urna nulla ut nunc in fusce a. Lacinia nulla sapien malesuada amet tortor cras ut sapien. Metus nibh facilisis sed pellentesque adipiscing venenatis faucibus nisi pellentesque adipiscing venenatis faucibus nisi'],
            ],
            'events' => ['title' => 'Event & Webinars', 'description' => $lorem, 'button_label' => 'Read More', 'button_url' => '#events'],
            'blogs' => ['title' => 'Blogs', 'description' => $lorem, 'image' => $this->img('blogs-bg')],
        ];
        foreach ($sections as $key => $data) {
            PageSection::updateOrCreate(['key' => $key], $data);
        }

        foreach ([
            ['Classic', '#053588', '#74c5e3', 'blue'],
            ['Jungle', '#9dc96c', '#9dc96c', 'green'],
            ['Boost', '#7566ff', '#872062', 'purple'],
            ['Zero Sugar', '#007eb9', '#83cfe3', 'zero-sugar'],
        ] as $i => [$name, $color, $border, $f]) {
            Product::updateOrCreate(['slug' => str($name)->slug()], [
                'name' => $name, 'description' => $lorem, 'color' => $color, 'border_color' => $border,
                'background_image' => $this->img("$f-bg"), 'can_image' => $this->img("$f-can"),
                'button_url' => '#products', 'sort_order' => $i,
            ]);
        }

        AboutPoint::query()->delete();
        foreach (['Energy for Active People', 'Built for Every Challenge', 'Best Support for Performance', 'Premium Quality Standards'] as $i => $title) {
            AboutPoint::create(['title' => $title, 'body' => 'Cras dapibus. Vivamus elementum semper nisi', 'sort_order' => $i]);
        }

        SocialPhoto::query()->delete();
        foreach ([1, 2, 3] as $i => $n) {
            SocialPhoto::create(['image' => $this->img("social-photo-$n"), 'alt' => 'NON STOP community photo', 'sort_order' => $i]);
        }

        foreach ([
            ['Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus', 'event-1'],
            ['Upcoming event', 'event-2'],
            ['Upcoming event', 'event-1'],
        ] as $i => [$title, $image]) {
            Event::updateOrCreate(['slug' => 'event-'.($i + 1)], [
                'title' => $title, 'image' => $this->img($image), 'sort_order' => $i, 'published_at' => now(),
            ]);
        }

        BlogPost::updateOrCreate(['slug' => 'ingredient-breakdowns'], [
            'title' => 'Ingredient Breakdowns', 'excerpt' => $short, 'image' => $this->img('blog-main'),
            'is_featured' => true, 'published_at' => now(),
        ]);
        foreach (['Flavor & Product Launches', 'Market Trends', 'Health & Safety', 'Regional Guides'] as $i => $title) {
            BlogPost::updateOrCreate(['slug' => str($title)->slug()], [
                'title' => $title,
                'excerpt' => "Lorem ipsum dolor sit amet consectetur. A eleifend turpis sed orci\nest amet. Urna nulla ut nunc in fusce a.",
                'thumbnail' => $this->img('blog-thumb'), 'published_at' => now()->subDays($i + 1),
            ]);
        }
    }

    /** Import one Figma asset to the public disk (WebP for rasters). Returns the stored path or null. */
    private function img(string $name, bool $convert = true): ?string
    {
        foreach (['png', 'svg'] as $ext) {
            $file = "{$this->src}/{$name}.{$ext}";
            if (! is_file($file)) {
                continue;
            }
            $disk = Storage::disk('public');
            if ($ext === 'svg' || ! $convert || ! function_exists('imagewebp')) {
                $dest = "site/{$name}.{$ext}";
                $disk->put($dest, file_get_contents($file));
                return $dest;
            }
            $im = imagecreatefromstring(file_get_contents($file));
            imagepalettetotruecolor($im);
            imagealphablending($im, false);
            imagesavealpha($im, true);
            $w = imagesx($im);
            if ($w > 1920) {
                $im = imagescale($im, 1920);
            }
            ob_start();
            imagewebp($im, null, 85);
            $dest = "site/{$name}.webp";
            $disk->put($dest, ob_get_clean());
            return $dest;
        }
        return null;
    }
}
