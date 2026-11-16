<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{AboutPoint, AboutSlide, HeroSlide, BlogPost, Event, NavLink, PageSection, Product, Shop, SiteSetting, SocialPhoto};
use App\Support\Presenter as P;

class ContentController extends Controller
{
    private function published(string $model)
    {
        return $model::query()->where('is_published', true)
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    /** Everything the homepage needs in a single request. */
    public function home()
    {
        return response()->json(['data' => [
            'settings' => P::settings(SiteSetting::first()),
            'nav' => [
                'header' => NavLink::where('location', 'header')->where('is_active', true)->orderBy('sort_order')->get()->map(P::nav(...)),
                'footer' => NavLink::where('location', 'footer')->where('is_active', true)->orderBy('sort_order')->get()->map(P::nav(...)),
                'legal' => NavLink::where('location', 'legal')->where('is_active', true)->orderBy('sort_order')->get()->map(P::nav(...)),
            ],
            'shops' => Shop::where('is_active', true)->orderBy('sort_order')->get()->map(P::shop(...)),
            'sections' => PageSection::where('is_active', true)->get()->mapWithKeys(fn ($s) => [$s->key => P::section($s)]),
            'products' => Product::where('is_active', true)->orderBy('sort_order')->get()->map(P::product(...)),
            'hero_slides' => HeroSlide::where('is_active', true)->orderBy('sort_order')->get()->map(P::heroSlide(...)),
            'about_slides' => AboutSlide::where('is_active', true)->orderBy('sort_order')->get()->map(P::aboutSlide(...)),
            'about_points' => AboutPoint::where('is_active', true)->orderBy('sort_order')->get()->map(P::aboutPoint(...)),
            'social_photos' => SocialPhoto::where('is_active', true)->orderBy('sort_order')->get()->map(P::socialPhoto(...)),
            'events' => $this->published(Event::class)->orderBy('sort_order')->limit(3)->get()->map(fn ($e) => P::event($e)),
            'blogs' => $this->published(BlogPost::class)->orderByDesc('is_featured')->orderByDesc('published_at')->limit(13)->get()->map(fn ($b) => P::blog($b)),
        ]])->header('Cache-Control', 'public, max-age=60');
    }

    public function settings()
    {
        return response()->json(['data' => P::settings(SiteSetting::first())]);
    }

    public function products()
    {
        return response()->json(['data' => Product::where('is_active', true)->orderBy('sort_order')->get()->map(P::product(...))]);
    }

    public function events()
    {
        $page = $this->published(Event::class)->orderBy('sort_order')->orderByDesc('published_at')->paginate(12);
        return response()->json($this->paged($page, fn ($e) => P::event($e)));
    }

    public function event(string $slug)
    {
        $e = $this->published(Event::class)->where('slug', $slug)->firstOrFail();
        return response()->json(['data' => P::event($e, true)]);
    }

    public function blogs()
    {
        $page = $this->published(BlogPost::class)->orderByDesc('published_at')->paginate(12);
        return response()->json($this->paged($page, fn ($b) => P::blog($b)));
    }

    public function blog(string $slug)
    {
        $b = $this->published(BlogPost::class)->where('slug', $slug)->firstOrFail();
        return response()->json(['data' => P::blog($b, true)]);
    }

    private function paged($page, callable $map): array
    {
        return [
            'data' => collect($page->items())->map($map)->values(),
            'meta' => ['page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'last_page' => $page->lastPage()],
        ];
    }
}
