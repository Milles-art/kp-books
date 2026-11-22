<?php

namespace App\Http\Controllers\Api\V1\Books;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Services\Books\BookPresenter;
use Illuminate\Http\Request;

class AuthorController extends Controller
{
    public function index()
    {
        $authors = Author::withCount('books')
            ->orderBy('name')
            ->get()
            ->map(fn (Author $author) => [
                'slug' => $author->slug,
                'name' => $author->name,
                'role' => $author->role,
                'portrait_url' => $author->portrait_url,
                'location' => $author->location,
                'books_count' => $author->books_count,
            ])
            ->values();

        return response()->json(['data' => $authors]);
    }

    public function show(Request $request, Author $author)
    {
        $books = $author->books()
            ->published()
            ->get()
            ->map(fn ($book) => app(BookPresenter::class)->summary($book))
            ->values();

        return response()->json(['data' => [
            'slug' => $author->slug,
            'name' => $author->name,
            'role' => $author->role,
            'bio' => $author->bio,
            'portrait_url' => $author->portrait_url,
            'location' => $author->location,
            'books' => $books,
        ]]);
    }
}
