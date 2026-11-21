<?php

use App\Http\Controllers\Api\V1\Admin\BookAdminController;
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

    Route::get('{book:id}/pages/{page}', [BookAdminController::class, 'showPage'])->whereNumber('page');
    Route::put('{book:id}/pages/{page}', [BookAdminController::class, 'updatePage'])->whereNumber('page');
});
