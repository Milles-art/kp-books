<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookPage;
use App\Models\BookReadingProgress;
use App\Services\Books\BookAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BookPageController extends Controller
{
    /**
     * One page at a time. Locked pages are refused here and never leave the server.
     * Guests can read the free preview pages.
     */
    public function show(Request $request, Book $book, int $page, BookAccessService $service)
    {
        abort_unless($book->status === 'published', 404);
        abort_if($page < 1 || $page > $book->total_pages, 404);

        $user = $request->user('sanctum');
        $access = $service->accessFor($user, $book);
        $allowed = $service->allowedPages($book, $access);

        if ($page > $allowed) {
            return response()->json([
                'message' => 'This page is locked.',
                'code' => 'PAGE_LOCKED',
                'allowed_pages' => $allowed,
                'total_pages' => $book->total_pages,
                'min_payment' => min($book->min_payment, $service->remaining($book, $access)),
            ], 403);
        }

        $content = BookPage::where('book_id', $book->id)->where('page_no', $page)->value('content');
        abort_if($content === null, 404);

        if ($user) {
            BookReadingProgress::updateOrCreate(
                ['user_id' => $user->id, 'book_id' => $book->id],
                ['last_page' => $page]
            );
        }

        return response()
            ->json([
                'data' => [
                    'page' => $page,
                    'total_pages' => $book->total_pages,
                    'allowed_pages' => $allowed,
                    'content' => $content,
                    // The client draws this faintly over every page to discourage sharing screenshots.
                    'watermark' => $user
                        ? Str::mask((string) ($user->phone ?? $user->email), '•', 3, -2)
                        : 'Preview',
                ],
            ])
            ->header('Cache-Control', 'private, no-store');
    }
}
