<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    require __DIR__ . '/api_books.php';

    Route::prefix('admin')->middleware(['auth:sanctum', \App\Http\Middleware\EnsureUserIsAdmin::class])->group(function () {
        require __DIR__ . '/api_admin_books.php';
    });
});