<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_loads(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_guests_are_redirected(): void
    {
        $this->get('/admin/products')->assertRedirect('/admin/login');
    }

    public function test_every_resource_index_and_edit_page_renders(): void
    {
        config(['filesystems.disks.public.root' => sys_get_temp_dir().'/ns-test-public']);
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::first());

        $slugs = [
            'products' => \App\Models\Product::class,
            'shops' => \App\Models\Shop::class,
            'nav-links' => \App\Models\NavLink::class,
            'page-sections' => \App\Models\PageSection::class,
            'about-points' => \App\Models\AboutPoint::class,
            'social-photos' => \App\Models\SocialPhoto::class,
            'events' => \App\Models\Event::class,
            'blog-posts' => \App\Models\BlogPost::class,
        ];

        foreach ($slugs as $slug => $model) {
            $this->get("/admin/$slug")->assertOk();
            $this->get("/admin/$slug/".$model::first()->getKey().'/edit')->assertOk();
        }
        foreach (['enquiries', 'site-settings'] as $slug) {
            $this->get("/admin/$slug")->assertOk();
        }
        $this->get('/admin/products/create')->assertOk();
    }
}
