<?php

use App\Http\Controllers\Api\V1\BookController;
use App\Http\Controllers\Api\V1\BookPageController;
use App\Http\Controllers\Api\V1\BookPaymentController;
use App\Http\Controllers\Api\V1\LibraryController;
use Illuminate\Support\Facades\Route;

// Include from routes/api.php inside the existing Route::prefix('v1') group:
//   require __DIR__ . '/books.php';

Route::prefix('books')->group(function () {
    Route::get('/', [BookController::class, 'index']);
    Route::get('{book:slug}', [BookController::class, 'show']);

    // Guests can read the free preview pages; the controller checks access per page.
    Route::get('{book:slug}/pages/{page}', [BookPageController::class, 'show'])
        ->whereNumber('page')
        ->middleware('throttle:book-pages');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('{book:slug}/quote', [BookPaymentController::class, 'quote']);
        Route::post('{book:slug}/payments', [BookPaymentController::class, 'store']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('library', [LibraryController::class, 'index']);
    Route::post('payments/{payment}/simulate-confirm', [BookPaymentController::class, 'simulateConfirm']);
});

// Provider webhook (no login; the gateway verifies the signature).
Route::post('payments/callback/{provider}', [BookPaymentController::class, 'callback']);
