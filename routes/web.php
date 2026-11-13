<?php

use Illuminate\Support\Facades\Route;

// The backend is API-only; the admin lives in the nonstop_cms app.
Route::get('/', fn () => response()->json([
    'name' => 'Non Stop API',
    'version' => 'v1',
    'endpoints' => url('/api/v1/home'),
]));
