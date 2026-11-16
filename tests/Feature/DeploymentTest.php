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
    use RefreshDatabase;

    private array $touched = [];

    private function env(string $key, ?string $value): void
    {
        $this->touched[] = $key;
        if ($value === null) {
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);
        } else {
            putenv("$key=$value");
            $_ENV[$key] = $_SERVER[$key] = $value;
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->touched as $key) {
            $this->env($key, null);
        }
        parent::tearDown();
    }

    public function test_public_disk_is_local_by_default(): void
    {
        $this->assertSame('local', config('filesystems.disks.public.driver'));
    }

    public function test_public_disk_switches_to_any_s3_compatible_bucket(): void
    {
        foreach (['PUBLIC_DISK_DRIVER' => 's3', 'AWS_ACCESS_KEY_ID' => 'k', 'AWS_SECRET_ACCESS_KEY' => 's', 'AWS_BUCKET' => 'nonstop',
            'AWS_URL' => 'https://cdn.example.com', 'AWS_ENDPOINT' => 'https://acct.r2.cloudflarestorage.com', 'AWS_DEFAULT_REGION' => 'auto'] as $k => $v) {
            $this->env($k, $v);
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

        $this->env('FORCE_SEED', 'true');
        $this->seed(DatabaseSeeder::class);
        $this->assertSame('NON STOP', SiteSetting::first()->site_name);
    }

    public function test_seeding_uses_the_bundled_images_on_a_fresh_server(): void
    {
        $this->env('SEED_ASSETS_PATH', '/nonexistent/path');
        config(['filesystems.disks.public.root' => sys_get_temp_dir().'/ns-test-public2']);
        $this->seed(DatabaseSeeder::class);

        $this->assertNotNull(\App\Models\Product::first()->can_image);
        $this->assertTrue(Storage::disk('public')->exists(\App\Models\Product::first()->can_image));
    }
}
