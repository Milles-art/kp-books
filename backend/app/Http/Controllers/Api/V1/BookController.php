<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookAccess;
use App\Models\BookReadingProgress;
use App\Services\Books\BookAccessService;
use App\Services\Books\BookPresenter;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function index(Request $request, BookPresenter $presenter)
    {
        $user = $request->user('sanctum');
        $books = Book::published()->orderBy('title')->get();

        $access = $user
            ? BookAccess::where('user_id', $user->id)->whereIn('book_id', $books->pluck('id'))->get()->keyBy('book_id')
            : collect();

        return response()->json([
            'data' => $books->map(fn (Book $b) => $presenter->summary($b, $access->get($b->id)))->values(),
        ]);
    }

    public function show(Request $request, Book $book, BookAccessService $service, BookPresenter $presenter)
    {
        abort_unless($book->status === 'published', 404);

        $user = $request->user('sanctum');
        $access = $service->accessFor($user, $book);
        $last = $user
            ? BookReadingProgress::where('user_id', $user->id)->where('book_id', $book->id)->value('last_page')
            : null;

        return response()->json([
            'data' => $presenter->summary($book, $access, $last) + [
                'options' => $presenter->options($book, $access),
            ],
        ]);
    }
}
