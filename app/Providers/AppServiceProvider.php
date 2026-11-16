<?php

namespace App\Providers;

use App\Support\DatabaseFilesystemAdapter;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Storage driver "database": keeps uploads in the shared database (demo-friendly, no bucket).
        Storage::extend('database', function ($app, array $config) {
            $adapter = new DatabaseFilesystemAdapter($config['url'] ?? '');

            return new FilesystemAdapter(new Filesystem($adapter, $config), $adapter, $config);
        });
    }
}
