<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Author;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AuthorAdminController extends Controller
{
    public function index()
    {
        $authors = Author::withCount('books')
            ->orderBy('name')
            ->get()
            ->map(fn (Author $author) => $this->present($author))
            ->values();

        return response()->json(['data' => $authors]);
    }

    public function show(Author $author)
    {
        return response()->json(['data' => $this->present($author)]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:authors,slug'],
            'role' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:4000'],
            'location' => ['nullable', 'string', 'max:120'],
        ]);

        $data['slug'] = $data['slug'] ?? null;
        $data['slug'] = $data['slug'] ?: (Str::slug($data['name']) ?: 'author');
        $author = Author::create($data);

        return response()->json(['data' => $this->present($author)], 201);
    }

    public function update(Request $request, Author $author)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'slug' => ['sometimes', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:authors,slug,' . $author->id],
            'role' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:4000'],
            'location' => ['nullable', 'string', 'max:120'],
        ]);

        $author->update($data);

        return response()->json(['data' => $this->present($author->refresh())]);
    }

    public function destroy(Author $author)
    {
        abort_if($author->books()->exists(), 409, 'Move the author’s books to another author first.');
        $author->delete();

        return response()->json(['data' => ['ok' => true]]);
    }

    public function portrait(Request $request, Author $author)
    {
        $data = $request->validate([
            'portrait' => ['required', 'file', 'image', 'max:' . ((int) config('kpbook.max_upload_mb') * 1024)],
        ]);

        $file = $data['portrait'];
        $disk = Storage::disk('public');
        $stored = $disk->putFileAs('author-portraits', $file, Str::uuid() . '.' . $file->getClientOriginalExtension());

        if ($author->portrait_path && str_starts_with($author->portrait_path, 'author-portraits/')) {
            $disk->delete($author->portrait_path);
        }

        $author->update(['portrait_path' => $stored]);

        return response()->json(['data' => $this->present($author->refresh())]);
    }

    private function present(Author $author): array
    {
        return [
            'id' => $author->id,
            'slug' => $author->slug,
            'name' => $author->name,
            'role' => $author->role,
            'bio' => $author->bio,
            'location' => $author->location,
            'portrait_url' => $author->portrait_url,
            'books_count' => $author->books_count ?? $author->books()->count(),
        ];
    }
}
