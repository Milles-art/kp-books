<?php

namespace App\Http\Controllers\Api\V1\Books;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookCollection;
use App\Models\User;
use App\Models\WishlistItem;
use App\Services\Books\BookAccessService;
use App\Services\Books\BookPresenter;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $saved = WishlistItem::where('user_id', $user->id)
            ->with('book')
            ->latest()
            ->get()
            ->map(fn ($item) => $this->present($item->book, $user))
            ->values();

        $collections = BookCollection::where('user_id', $user->id)
            ->with('books')
            ->orderBy('name')
            ->get()
            ->map(fn ($collection) => [
                'id' => $collection->id,
                'name' => $collection->name,
                'count' => $collection->books->count(),
                'books' => $collection->books
                    ->map(fn (Book $book) => $this->present($book, $user))
                    ->values(),
            ])
            ->values();

        return response()->json(['data' => ['saved' => $saved, 'collections' => $collections]]);
    }

    public function toggle(Request $request, Book $book)
    {
        $user = $request->user();
        $existing = WishlistItem::where('user_id', $user->id)->where('book_id', $book->id)->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['data' => ['saved' => false]]);
        }

        WishlistItem::create(['user_id' => $user->id, 'book_id' => $book->id]);

        return response()->json(['data' => ['saved' => true]]);
    }

    public function storeCollection(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:40'],
        ]);

        $name = trim(preg_replace('/\s+/', ' ', $data['name']));
        abort_if($name === '', 422, 'Give the collection a name.');

        $exists = BookCollection::where('user_id', $request->user()->id)
            ->whereRaw('lower(name) = ?', [mb_strtolower($name)])
            ->exists();
        abort_if($exists, 409, 'You already have a collection with that name.');

        $collection = BookCollection::create(['user_id' => $request->user()->id, 'name' => $name]);

        return response()->json(['data' => ['id' => $collection->id, 'name' => $collection->name]], 201);
    }

    public function updateCollection(Request $request, BookCollection $collection)
    {
        $this->owns($request->user(), $collection);
        $data = $request->validate(['name' => ['required', 'string', 'max:40']]);

        $name = trim(preg_replace('/\s+/', ' ', $data['name']));
        abort_if($name === '', 422, 'Give the collection a name.');

        $exists = BookCollection::where('user_id', $request->user()->id)
            ->where('id', '!=', $collection->id)
            ->whereRaw('lower(name) = ?', [mb_strtolower($name)])
            ->exists();
        abort_if($exists, 409, 'You already have a collection with that name.');

        $collection->update(['name' => $name]);

        return response()->json(['data' => ['id' => $collection->id, 'name' => $collection->name]]);
    }

    public function destroyCollection(Request $request, BookCollection $collection)
    {
        $this->owns($request->user(), $collection);
        $collection->delete();

        return response()->json(['data' => ['ok' => true]]);
    }

    public function addBook(Request $request, BookCollection $collection, Book $book)
    {
        $this->owns($request->user(), $collection);
        $collection->books()->syncWithoutDetaching([$book->id]);

        return response()->json(['data' => ['added' => true]]);
    }

    public function removeBook(Request $request, BookCollection $collection, Book $book)
    {
        $this->owns($request->user(), $collection);
        $collection->books()->detach($book->id);

        return response()->json(['data' => ['removed' => true]]);
    }

    private function owns(User $user, BookCollection $collection): void
    {
        abort_unless($collection->user_id === $user->id, 404);
    }

    private function present(Book $book, User $user): array
    {
        $access = app(BookAccessService::class)->accessFor($user, $book);

        return app(BookPresenter::class)->summary($book, $access);
    }
}
