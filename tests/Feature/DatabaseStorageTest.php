<?php

namespace Tests\Feature;

use App\Models\Product;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DatabaseStorageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        putenv('PUBLIC_DISK_DRIVER=database');
        $_ENV['PUBLIC_DISK_DRIVER'] = $_SERVER['PUBLIC_DISK_DRIVER'] = 'database';
        parent::setUp();
    }

    protected function tearDown(): void
    {
        putenv('PUBLIC_DISK_DRIVER');
        unset($_ENV['PUBLIC_DISK_DRIVER'], $_SERVER['PUBLIC_DISK_DRIVER']);
        parent::tearDown();
    }

    public function test_files_round_trip_through_the_database_disk(): void
    {
        $disk = Storage::disk('public');
        $binary = random_bytes(5000);

        $disk->put('site/a.txt', 'hello');
        $disk->put('site/pic.bin', $binary);
        $disk->put('site/logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>');

        $this->assertTrue($disk->exists('site/a.txt'));
        $this->assertSame('hello', $disk->get('site/a.txt'));
        $this->assertSame($binary, $disk->get('site/pic.bin'));
        $this->assertSame(5000, $disk->size('site/pic.bin'));
        $this->assertSame('image/svg+xml', $disk->mimeType('site/logo.svg'));
        $this->assertSame('public', $disk->getVisibility('site/a.txt'));
        $this->assertSame(rtrim(config('app.url'), '/').'/media/site/a.txt', $disk->url('site/a.txt'));
        $this->assertEqualsCanonicalizing(['site/a.txt', 'site/pic.bin', 'site/logo.svg'], $disk->files('site'));

        $disk->put('site/a.txt', 'overwritten');                 // upsert, no duplicate row
        $this->assertSame('overwritten', $disk->get('site/a.txt'));
        $this->assertSame(1, DB::table('media_files')->where('path', 'site/a.txt')->count());

        $disk->copy('site/a.txt', 'site/b.txt');
        $disk->move('site/b.txt', 'other/c.txt');
        $this->assertTrue($disk->exists('other/c.txt'));
        $this->assertFalse($disk->exists('site/b.txt'));

        $disk->delete('site/a.txt');
        $this->assertFalse($disk->exists('site/a.txt'));
        $disk->deleteDirectory('site');
        $this->assertSame([], $disk->allFiles('site'));
        $this->assertTrue($disk->exists('other/c.txt'));
    }

    public function test_stream_writes_work_like_filament_uploads(): void
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, 'streamed-content');
        rewind($stream);

        Storage::disk('public')->writeStream('uploads/x.txt', $stream);
        $this->assertSame('streamed-content', Storage::disk('public')->get('uploads/x.txt'));
    }

    public function test_media_route_serves_files_with_caching_and_byte_ranges(): void
    {
        Storage::disk('public')->put('reels/clip.mp4', '0123456789');

        $r = $this->get('/media/reels/clip.mp4')->assertOk();
        $this->assertSame('0123456789', $r->getContent());
        $this->assertSame('video/mp4', $r->headers->get('Content-Type'));
        $this->assertSame('bytes', $r->headers->get('Accept-Ranges'));

        $this->withHeader('Range', 'bytes=2-4')->get('/media/reels/clip.mp4')->assertStatus(206)->assertHeader('Content-Range', 'bytes 2-4/10')->assertContent('234');
        $this->withHeader('Range', 'bytes=7-')->get('/media/reels/clip.mp4')->assertStatus(206)->assertContent('789');
        $this->withHeader('Range', 'bytes=-3')->get('/media/reels/clip.mp4')->assertStatus(206)->assertContent('789');
        $this->withHeader('Range', 'bytes=50-60')->get('/media/reels/clip.mp4')->assertStatus(416);

        $etag = $r->headers->get('ETag');
        $this->withHeader('If-None-Match', $etag)->get('/media/reels/clip.mp4')->assertStatus(304);

        $this->get('/media/reels/missing.mp4')->assertNotFound();
    }

    public function test_seeding_puts_the_demo_content_and_reels_into_the_database(): void
    {
        $this->seed(DatabaseSeeder::class);

        $can = Product::first()->can_image;
        $this->assertTrue(DB::table('media_files')->where('path', $can)->exists());
        $this->get('/media/'.$can)->assertOk()->assertHeader('Content-Type', 'image/webp');

        $videos = DB::table('social_photos')->whereNotNull('video')->pluck('video');
        $this->assertCount(4, $videos);
        foreach ($videos as $video) {
            $this->assertTrue(DB::table('media_files')->where('path', $video)->exists(), "$video missing");
        }
        $this->getJson('/api/v1/home')->assertOk()->assertJsonPath('data.social_photos.0.video', fn ($v) => str_contains($v, '/media/reels/'));
    }
}
