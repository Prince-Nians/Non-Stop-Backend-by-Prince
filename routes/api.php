<?php

use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\EnquiryController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::middleware('throttle:120,1')->group(function () {
        Route::get('home', [ContentController::class, 'home']);
        Route::get('settings', [ContentController::class, 'settings']);
        Route::get('products', [ContentController::class, 'products']);
        Route::get('events', [ContentController::class, 'events']);
        Route::get('events/{slug}', [ContentController::class, 'event']);
        Route::get('blogs', [ContentController::class, 'blogs']);
        Route::get('blogs/{slug}', [ContentController::class, 'blog']);
    });

    // Form endpoints are throttled harder to limit spam.
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('contact', [EnquiryController::class, 'contact']);
        Route::post('partner', [EnquiryController::class, 'partner']);
    });
});
