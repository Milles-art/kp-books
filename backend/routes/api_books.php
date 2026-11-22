<?php

use App\Http\Controllers\Api\V1\Books\AuthorController;
use App\Http\Controllers\Api\V1\Books\BookController;
use App\Http\Controllers\Api\V1\Books\BookPageController;
use App\Http\Controllers\Api\V1\Books\BookPaymentController;
use App\Http\Controllers\Api\V1\Books\EntitlementController;
use App\Http\Controllers\Api\V1\Books\LibraryController;
use App\Http\Controllers\Api\V1\Books\ReadingSessionController;
use App\Http\Controllers\Api\V1\Books\RefundController;
use App\Http\Controllers\Api\V1\Books\WishlistController;
use Illuminate\Support\Facades\Route;

// Included from routes/api.php inside Route::prefix('v1').

// Public author pages.
Route::get('authors', [AuthorController::class, 'index']);
Route::get('authors/{author:slug}', [AuthorController::class, 'show']);

Route::prefix('books')->group(function () {
    Route::get('/', [BookController::class, 'index']);

    // Protected routes are declared before {book:slug} so literal paths win matching.
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('saved', [WishlistController::class, 'index']);
        Route::post('{book:slug}/save', [WishlistController::class, 'toggle']);

        Route::post('collections', [WishlistController::class, 'storeCollection']);
        Route::patch('collections/{collection:id}', [WishlistController::class, 'updateCollection']);
        Route::delete('collections/{collection:id}', [WishlistController::class, 'destroyCollection']);
        Route::post('collections/{collection:id}/books/{book:slug}', [WishlistController::class, 'addBook'])->withoutScopedBindings();
        Route::delete('collections/{collection:id}/books/{book:slug}', [WishlistController::class, 'removeBook'])->withoutScopedBindings();

        Route::get('devices', [ReadingSessionController::class, 'index']);
        Route::delete('devices/{session:id}', [ReadingSessionController::class, 'destroy']);

        Route::get('{book:slug}/entitlement', [EntitlementController::class, 'show']);
    });

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
    Route::post('payments/{payment}/refund/request', [RefundController::class, 'store']);
});

// Provider webhook (no login; the gateway verifies the signature).
Route::post('payments/callback/{provider}', [BookPaymentController::class, 'callback']);
