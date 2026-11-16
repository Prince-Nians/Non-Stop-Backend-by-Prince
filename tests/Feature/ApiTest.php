<?php

namespace Tests\Feature;

use App\Models\{BlogPost, Enquiry};
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.disks.public.root' => sys_get_temp_dir().'/ns-test-public']);
        $this->seed(DatabaseSeeder::class);
    }

    public function test_home_returns_all_sections(): void
    {
        $r = $this->getJson('/api/v1/home')->assertOk();
        $r->assertJsonStructure(['data' => ['settings', 'nav' => ['header', 'footer', 'legal'], 'shops', 'sections', 'products', 'about_points', 'about_slides', 'hero_slides', 'social_photos', 'events', 'blogs']]);
        $this->assertCount(4, $r->json('data.products'));
        $this->assertCount(3, $r->json('data.shops'));
        $this->assertCount(6, $r->json('data.social_photos'));
        $this->assertCount(10, $r->json('data.assets'));
        $this->assertStringStartsWith('http', $r->json('data.assets.hero_can_boost.image'));
        $this->assertSame('NON STOP', $r->json('data.sections.about.extra.ribbon_text'));
        $this->assertSame('Quick Links', $r->json('data.sections.footer.extra.quick_links_title'));
        $this->assertSame('Close', $r->json('data.sections.labels.extra.close'));
        $this->assertSame('https://www.instagram.com/reel/DeErd6szLeQ/', $r->json('data.social_photos.0.instagram_url'));
        $this->assertNull($r->json('data.social_photos.3.image'));
        $this->assertCount(4, $r->json('data.about_slides'));
        $this->assertCount(4, $r->json('data.hero_slides'));
        $this->assertSame('stadium', $r->json('data.hero_slides.0.kind'));
        $this->assertStringStartsWith('http', $r->json('data.hero_slides.1.image'));
        $this->assertSame('#71c603', $r->json('data.about_slides.1.colors.ribbon_a'));
        $this->assertCount(7, $r->json('data.nav.header'));
        $this->assertSame('Our Products', $r->json('data.sections.products.title'));
        $this->assertStringStartsWith('http', $r->json('data.products.0.can_image'));
    }

    public function test_blog_detail_and_unpublished_hidden(): void
    {
        $this->getJson('/api/v1/blogs/market-trends')->assertOk()->assertJsonPath('data.title', 'Market Trends');
        BlogPost::where('slug', 'market-trends')->update(['is_published' => false]);
        $this->getJson('/api/v1/blogs/market-trends')->assertNotFound();
        $this->getJson('/api/v1/blogs')->assertOk()->assertJsonCount(8, 'data');
    }

    public function test_contact_form_stores_and_validates(): void
    {
        $this->postJson('/api/v1/contact', [])->assertStatus(422);
        $this->postJson('/api/v1/contact', ['name' => 'Asha', 'email' => 'asha@example.com', 'message' => 'Hello'])->assertCreated();
        $this->assertDatabaseHas('enquiries', ['type' => 'contact', 'email' => 'asha@example.com', 'status' => 'new']);
    }

    public function test_partner_form_and_honeypot(): void
    {
        $this->postJson('/api/v1/partner', ['name' => 'Ravi', 'email' => 'ravi@example.com', 'company' => 'Shop'])->assertCreated();
        $this->assertSame(1, Enquiry::where('type', 'partner')->count());

        $this->postJson('/api/v1/contact', ['name' => 'Bot', 'email' => 'b@example.com', 'message' => 'x', 'website' => 'spam'])->assertStatus(422);
        $this->assertSame(0, Enquiry::where('type', 'contact')->count());
    }

    public function test_instagram_links_are_cleaned_and_validated(): void
    {
        $clean = fn ($u) => \App\Support\Presenter::instagramPermalink($u);
        $this->assertSame('https://www.instagram.com/reel/DeErd6szLeQ/', $clean('https://www.instagram.com/reel/DeErd6szLeQ/?utm_source=ig_web_button_share_sheet&dlrf=abc'));
        $this->assertSame('https://www.instagram.com/p/DfrwDMJsgFO/', $clean('https://instagram.com/p/DfrwDMJsgFO?utm_source=x'));
        $this->assertNull($clean('https://evil.example/reel/abc/'));
        $this->assertNull($clean('javascript:alert(1)'));
        $this->assertNull($clean(null));
    }
}
