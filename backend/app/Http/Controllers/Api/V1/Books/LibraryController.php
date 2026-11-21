<?php

namespace App\Http\Controllers\Api\V1\Books;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookAccess;
use App\Models\BookReadingProgress;
use App\Services\Books\BookPresenter;
use Illuminate\Http\Request;

class LibraryController extends Controller
{
    /** My library: every book the reader has paid for or started reading. */
    public function index(Request $request, BookPresenter $presenter)
    {
        $user = $request->user();

        $access = BookAccess::where('user_id', $user->id)->get()->keyBy('book_id');
        $progress = BookReadingProgress::where('user_id', $user->id)->get()->keyBy('book_id');

        $ids = $access->keys()->merge($progress->keys())->unique()->values();
        $books = Book::published()->whereIn('id', $ids)->get();

        return response()->json([
            'data' => $books->map(fn (Book $b) => $presenter->summary(
                $b,
                $access->get($b->id),
                $progress->get($b->id)?->last_page
            ))->values(),
        ]);
    }
}
