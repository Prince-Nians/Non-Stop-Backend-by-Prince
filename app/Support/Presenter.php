<?php

namespace App\Support;

use App\Models\{AboutPoint, AboutSlide, HeroSlide, BlogPost, Event, NavLink, PageSection, Product, Shop, SiteSetting, SocialPhoto};
use Illuminate\Support\Facades\Storage;

/** Turns CMS models into the public JSON shapes (absolute image URLs, no internal columns). */
class Presenter
{
    public static function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        return str_starts_with($path, 'http') ? $path : Storage::disk('public')->url($path);
    }

    public static function settings(?SiteSetting $s): array
    {
        $s ??= new SiteSetting();
        return [
            'site_name' => $s->site_name,
            'logo' => self::url($s->logo),
            'phone' => $s->phone,
            'email' => $s->email,
            'copyright' => $s->copyright_text,
            'social' => [
                'facebook' => $s->facebook_url, 'youtube' => $s->youtube_url,
                'linkedin' => $s->linkedin_url, 'twitter' => $s->twitter_url,
            ],
            'seo' => ['title' => $s->seo_title, 'description' => $s->seo_description],
        ];
    }

    public static function shop(Shop $m): array
    {
        return ['name' => $m->name, 'slug' => $m->slug, 'url' => $m->url, 'logo' => self::url($m->logo)];
    }

    public static function nav(NavLink $m): array
    {
        return ['label' => $m->label, 'url' => $m->url];
    }

    public static function section(PageSection $m): array
    {
        return [
            'title' => $m->title, 'subtitle' => $m->subtitle, 'description' => $m->description,
            'button' => $m->button_label ? ['label' => $m->button_label, 'url' => $m->button_url] : null,
            'image' => self::url($m->image), 'image_secondary' => self::url($m->image_secondary),
            'extra' => (object) ($m->extra ?? []),
        ];
    }

    public static function product(Product $m): array
    {
        return [
            'name' => $m->name, 'slug' => $m->slug, 'description' => $m->description,
            'color' => $m->color, 'border_color' => $m->border_color,
            'background_image' => self::url($m->background_image), 'can_image' => self::url($m->can_image),
            'button' => ['label' => $m->button_label, 'url' => $m->button_url],
        ];
    }

    public static function aboutPoint(AboutPoint $m): array
    {
        return ['title' => $m->title, 'body' => $m->body];
    }

    public static function heroSlide(HeroSlide $m): array
    {
        return [
            'kind' => $m->kind, 'name' => $m->name, 'image' => self::url($m->image),
            'mobile_image' => self::url($m->mobile_image), 'alt' => $m->alt, 'url' => $m->url,
        ];
    }

    public static function aboutSlide(AboutSlide $m): array
    {
        return [
            'name' => $m->name, 'can_image' => self::url($m->can_image),
            'colors' => [
                'ribbon_a' => $m->ribbon_color_a, 'ribbon_b' => $m->ribbon_color_b, 'title' => $m->title_color,
                'number' => $m->number_color, 'point_title' => $m->point_title_color, 'button' => $m->button_color,
            ],
        ];
    }

    public static function socialPhoto(SocialPhoto $m): array
    {
        return ['image' => self::url($m->image), 'alt' => $m->alt, 'url' => $m->url];
    }

    public static function event(Event $m, bool $full = false): array
    {
        return array_filter([
            'title' => $m->title, 'slug' => $m->slug, 'excerpt' => $m->excerpt, 'image' => self::url($m->image),
            'starts_at' => $m->starts_at?->toIso8601String(), 'location' => $m->location, 'url' => $m->url,
            'published_at' => $m->published_at?->toIso8601String(),
            'body' => $full ? $m->body : null,
            'seo' => $full ? ['title' => $m->seo_title, 'description' => $m->seo_description] : null,
        ], fn ($v) => $v !== null);
    }

    public static function blog(BlogPost $m, bool $full = false): array
    {
        return array_filter([
            'title' => $m->title, 'slug' => $m->slug, 'tag' => $m->tag, 'excerpt' => $m->excerpt,
            'image' => self::url($m->image), 'thumbnail' => self::url($m->thumbnail),
            'featured' => $m->is_featured, 'published_at' => $m->published_at?->toIso8601String(),
            'body' => $full ? $m->body : null,
            'seo' => $full ? ['title' => $m->seo_title, 'description' => $m->seo_description] : null,
        ], fn ($v) => $v !== null);
    }
}
