<?php

namespace Database\Seeders;

use App\Models\{AboutPoint, AboutSlide, HeroSlide, BlogPost, Event, NavLink, PageSection, Product, Shop, SiteSetting, SocialPhoto};
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
        $core = 'consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis';
        // "\n" marks the line break the Figma design shows on desktop.
        $productsText = $core."\npretium. ".$core.' pretium.';
        $eventsText = $core."\npretium ".$core.' pretium';
        $blogsText = $lorem.' at massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium.';
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
            'products' => ['title' => 'Our Products', 'description' => $productsText],
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
            'events' => ['title' => 'Event & Webinars', 'description' => $eventsText, 'button_label' => 'Read More', 'button_url' => '#events'],
            'blogs' => ['title' => 'Blogs', 'description' => $blogsText, 'image' => $this->img('blogs-bg')],
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

        HeroSlide::query()->delete();
        foreach ([
            ['stadium', 'Stadium', null, null],
            ['banner', 'Jungle', 'hero-banner-jungle', 'STAY NONSTOP — NON STOP Jungle'],
            ['banner', 'Boost', 'hero-banner-boost', 'STAY NONSTOP — NON STOP Boost'],
            ['banner', 'Zero Sugar', 'hero-banner-zero', 'STAY NONSTOP — NON STOP Classic Zero Sugar'],
        ] as $i => [$kind, $name, $file, $alt]) {
            HeroSlide::create(['kind' => $kind, 'name' => $name, 'image' => $file ? $this->img($file) : null, 'alt' => $alt, 'sort_order' => $i + 1]);
        }

        AboutSlide::query()->delete();
        foreach ([
            ['Classic', 'about-can', '#0062a0', '#21306a', '#053588', '#0062a0', '#21306a', '#008240'],
            ['Jungle', 'about-can-jungle', '#71c603', '#027d2e', '#027d2e', '#71c603', '#027d2e', '#027d2e'],
            ['Boost', 'about-can-boost', '#872062', '#321862', '#321862', '#872062', '#321862', '#008240'],
            ['Zero Sugar', 'about-can-zero', '#77c6db', '#0091ae', '#053588', '#77c6db', '#008fad', '#008240'],
        ] as $i => [$name, $can, $ra, $rb, $title, $num, $pt, $btn]) {
            AboutSlide::create([
                'name' => $name, 'can_image' => $this->img($can),
                'ribbon_color_a' => $ra, 'ribbon_color_b' => $rb, 'title_color' => $title,
                'number_color' => $num, 'point_title_color' => $pt, 'button_color' => $btn, 'sort_order' => $i,
            ]);
        }

        AboutPoint::query()->delete();
        foreach (['Energy for Active People', 'Built for Every Challenge', 'Best Support for Performance', 'Premium Quality Standards'] as $i => $title) {
            AboutPoint::create(['title' => $title, 'body' => 'Cras dapibus. Vivamus elementum semper nisi', 'sort_order' => $i]);
        }

        SocialPhoto::query()->delete();
        // Reels with an uploaded video come first: the first three become the autoplaying cards on the page.
        // Every item opens in the popup (video if present, otherwise the Instagram embed).
        foreach ([
            ['https://www.instagram.com/reel/DeErd6szLeQ/', 'reel-1'],
            ['https://www.instagram.com/reel/DftWkHOxShP/', 'reel-2'],
            ['https://www.instagram.com/reel/Dfo-utkIKPn/', 'reel-3'],
            ['https://www.instagram.com/reel/DfPfpM-NNF3/', 'reel-4'],
            ['https://www.instagram.com/p/DfrwDMJsgFO/', null],
            ['https://www.instagram.com/reel/DdWUksUFEAZ/', null],
        ] as $i => [$instagram, $video]) {
            SocialPhoto::create([
                // Fallback cover only for the cards that have no video yet.
                'image' => ($video || $i > 2) ? null : $this->img('social-photo-'.($i + 1)),
                'video' => $video ? $this->video($video) : null,
                'alt' => 'NON STOP on Instagram',
                'instagram_url' => $instagram,
                'sort_order' => $i,
            ]);
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
        foreach (['Flavor & Product Launches', 'Market Trends', 'Health & Safety', 'Regional Guides', 'Training & Performance', 'Hydration Guide', 'Recipes & Mixers', 'Community Stories'] as $i => $title) {
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

    /** Reel videos live in storage/app/public/reels; if missing, copy from the seed assets' sibling "reels" folder. */
    private function video(string $name): ?string
    {
        $disk = Storage::disk('public');
        $dest = "reels/{$name}.mp4";
        if ($disk->exists($dest)) {
            return $dest;
        }
        $source = dirname($this->src)."/reels/{$name}.mp4";
        if (is_file($source)) {
            $disk->put($dest, file_get_contents($source));
            return $dest;
        }
        return null;
    }
}
