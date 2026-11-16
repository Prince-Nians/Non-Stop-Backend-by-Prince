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
        $r->assertJsonStructure(['data' => ['settings', 'nav' => ['header', 'footer', 'legal'], 'shops', 'sections', 'products', 'about_points', 'about_slides', 'social_photos', 'events', 'blogs']]);
        $this->assertCount(4, $r->json('data.products'));
        $this->assertCount(3, $r->json('data.shops'));
        $this->assertCount(4, $r->json('data.about_slides'));
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
        $this->getJson('/api/v1/blogs')->assertOk()->assertJsonCount(4, 'data');
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
}
