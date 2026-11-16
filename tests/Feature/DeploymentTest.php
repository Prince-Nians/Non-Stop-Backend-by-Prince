<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DeploymentTest extends TestCase
{
    use \Tests\Concerns\SetsEnv;

    protected function tearDown(): void
    {
        $this->restoreEnv();
        parent::tearDown();
    }

    use RefreshDatabase;


    public function test_public_disk_is_local_by_default(): void
    {
        $this->assertSame('local', config('filesystems.disks.public.driver'));
    }

    public function test_public_disk_switches_to_any_s3_compatible_bucket(): void
    {
        foreach (['PUBLIC_DISK_DRIVER' => 's3', 'AWS_ACCESS_KEY_ID' => 'k', 'AWS_SECRET_ACCESS_KEY' => 's', 'AWS_BUCKET' => 'nonstop',
            'AWS_URL' => 'https://cdn.example.com', 'AWS_ENDPOINT' => 'https://acct.r2.cloudflarestorage.com', 'AWS_DEFAULT_REGION' => 'auto'] as $k => $v) {
            $this->setEnv($k, $v);
        }
        $this->refreshApplication();

        $this->assertSame('s3', config('filesystems.disks.public.driver'));
        $this->assertSame('https://cdn.example.com/site/a.webp', Storage::disk('public')->url('site/a.webp'));
        // No per-file ACLs (modern S3 buckets and R2 reject them).
        $this->assertArrayNotHasKey('visibility', config('filesystems.disks.public'));
    }

    public function test_forwarded_https_headers_from_the_host_proxy_are_trusted(): void
    {
        Route::get('/_proxy-check', fn () => response()->json(['url' => url('/x'), 'secure' => request()->isSecure()]));

        $this->withServerVariables(['HTTP_X_FORWARDED_PROTO' => 'https', 'HTTP_X_FORWARDED_HOST' => 'api.example.com', 'HTTP_X_FORWARDED_PORT' => '443'])
            ->getJson('/_proxy-check')->assertOk()->assertJsonPath('secure', true)->assertJsonPath('url', 'https://api.example.com/x');
    }

    public function test_seeder_does_not_overwrite_existing_content_unless_forced(): void
    {
        config(['filesystems.disks.public.root' => sys_get_temp_dir().'/ns-test-public']);
        $this->seed(DatabaseSeeder::class);
        SiteSetting::first()->update(['site_name' => 'Edited by editor']);

        $this->seed(DatabaseSeeder::class); // second run, e.g. a redeploy
        $this->assertSame('Edited by editor', SiteSetting::first()->site_name);

        $this->setEnv('FORCE_SEED', 'true');
        $this->seed(DatabaseSeeder::class);
        $this->assertSame('NON STOP', SiteSetting::first()->site_name);
    }

    public function test_seeding_uses_the_bundled_images_on_a_fresh_server(): void
    {
        $this->setEnv('SEED_ASSETS_PATH', '/nonexistent/path');
        config(['filesystems.disks.public.root' => sys_get_temp_dir().'/ns-test-public2']);
        $this->seed(DatabaseSeeder::class);

        $this->assertNotNull(\App\Models\Product::first()->can_image);
        $this->assertTrue(Storage::disk('public')->exists(\App\Models\Product::first()->can_image));
    }

    public function test_an_interrupted_first_seed_is_resumed_not_skipped(): void
    {
        config(['filesystems.disks.public.root' => sys_get_temp_dir().'/ns-test-public3']);
        $this->seed(DatabaseSeeder::class);
        $this->assertNotNull(SiteSetting::first()->seeded_at); // finished -> marked

        // Simulate a crash half way: content exists but the marker was never set and later tables are empty.
        SiteSetting::query()->update(['seeded_at' => null]);
        \App\Models\BlogPost::query()->delete();
        \App\Models\Event::query()->delete();

        $this->seed(DatabaseSeeder::class); // the next start finishes the job
        $this->assertGreaterThan(0, \App\Models\BlogPost::count());
        $this->assertGreaterThan(0, \App\Models\Event::count());
        $this->assertNotNull(SiteSetting::first()->seeded_at);
    }

    public function test_contact_page_content_is_added_to_an_already_seeded_database_without_touching_edits(): void
    {
        config(['filesystems.disks.public.root' => sys_get_temp_dir().'/ns-test-public4']);
        $this->seed(DatabaseSeeder::class);

        // An editor changed a contact text and removed one FAQ.
        \App\Models\PageSection::where('key', 'contact_info')->update(['title' => 'Edited by editor']);
        \App\Models\Faq::first()->delete();
        // A database seeded before the contact page existed: its contact rows are missing.
        \App\Models\PageSection::whereIn('key', ['contact_hero', 'marquee'])->delete();

        $this->seed(DatabaseSeeder::class); // a redeploy (the main seeder skips; the additive one fills the gaps)

        $this->assertSame('Edited by editor', \App\Models\PageSection::where('key', 'contact_info')->value('title'));
        $this->assertNotNull(\App\Models\PageSection::where('key', 'contact_hero')->value('image'));
        $this->assertSame(1, \App\Models\PageSection::where('key', 'marquee')->count());
        $this->assertSame(4, \App\Models\Faq::count()); // FAQ items are not recreated once some exist
    }
}
