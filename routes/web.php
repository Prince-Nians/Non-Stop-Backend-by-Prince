<?php

use App\Http\Controllers\MediaController;
use Illuminate\Support\Facades\Route;

// The backend is API-only; the admin lives in the nonstop_cms app.
Route::get('/', fn () => response()->json([
    'name' => 'Non Stop API',
    'version' => 'v1',
    'endpoints' => url('/api/v1/home'),
]));

// Files kept in the database (PUBLIC_DISK_DRIVER=database).
Route::get('/media/{path}', [MediaController::class, 'show'])->where('path', '.*');
