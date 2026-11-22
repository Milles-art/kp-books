<?php

use App\Http\Controllers\Api\V1\Admin\AuthorAdminController;
use App\Http\Controllers\Api\V1\Admin\BookAdminController;
use App\Http\Controllers\Api\V1\Admin\RefundAdminController;
use Illuminate\Support\Facades\Route;

// Include this inside the existing admin route group (the one api_admin.php uses), so the
// admin login and permission checks already apply. Final paths look like /api/v1/admin/books.

Route::prefix('books')->group(function () {
    Route::get('/', [BookAdminController::class, 'index']);
    Route::post('/', [BookAdminController::class, 'store']);

    Route::get('{book:id}', [BookAdminController::class, 'show']);
    Route::patch('{book:id}', [BookAdminController::class, 'update']);
    Route::post('{book:id}/import', [BookAdminController::class, 'import']);
    Route::post('{book:id}/publish', [BookAdminController::class, 'publish']);
    Route::post('{book:id}/unpublish', [BookAdminController::class, 'unpublish']);
    Route::post('{book:id}/cover', [BookAdminController::class, 'cover']);

    Route::get('{book:id}/pages/{page}', [BookAdminController::class, 'showPage'])->whereNumber('page');
    Route::put('{book:id}/pages/{page}', [BookAdminController::class, 'updatePage'])->whereNumber('page');
});

// Author management.
Route::prefix('authors')->group(function () {
    Route::get('/', [AuthorAdminController::class, 'index']);
    Route::post('/', [AuthorAdminController::class, 'store']);
    Route::get('{author:slug}', [AuthorAdminController::class, 'show']);
    Route::patch('{author:slug}', [AuthorAdminController::class, 'update']);
    Route::delete('{author:slug}', [AuthorAdminController::class, 'destroy']);
    Route::post('{author:slug}/portrait', [AuthorAdminController::class, 'portrait']);
});

// Refund queue (partial-payment policy per Kipanya spec).
Route::prefix('refunds')->group(function () {
    Route::get('/', [RefundAdminController::class, 'index']);
    Route::post('{refund:id}/process', [RefundAdminController::class, 'process']);
});
