<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\Books\ImportBookJob;
use App\Models\Book;
use App\Models\BookPage;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Admin side of KP Book. Include routes/api_admin_books.php inside the existing admin
 * route group, which already applies the admin login and permission checks.
 */
class BookAdminController extends Controller
{
    private const FORMATS = 'pdf,epub,docx,doc,rtf,odt,wps,txt,md,html,htm,mobi,azw3,fb2';

    public function index()
    {
        $books = Book::query()
            ->withCount('accesses')
            ->withSum(['payments as revenue' => fn ($q) => $q->where('status', 'confirmed')], 'amount')
            ->latest()
            ->get();

        return response()->json(['data' => $books->map(fn (Book $b) => $this->present($b))->values()]);
    }

    public function show(Book $book)
    {
        return response()->json(['data' => $this->present($book)]);
    }

    /** Create a book from a metadata form plus the book file. Import runs in the background. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'subtitle' => ['nullable', 'string', 'max:200'],
            'author' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price_full' => ['required', 'integer', 'min:1000'],
            'free_preview_pages' => ['nullable', 'integer', 'min:0', 'max:50'],
            'min_payment' => ['nullable', 'integer', 'min:100'],
            'unlock_block' => ['nullable', 'integer', 'min:1', 'max:50'],
            'words_per_page' => ['nullable', 'integer', 'min:100', 'max:600'],
            'file' => $this->fileRules(true),
        ]);

        $book = Book::create([
            'slug' => $this->uniqueSlug($data['title']),
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'author' => $data['author'],
            'description' => $data['description'] ?? null,
            'price_full' => $data['price_full'],
            'total_pages' => 0,
            'free_preview_pages' => $data['free_preview_pages'] ?? 5,
            'min_payment' => $data['min_payment'] ?? 1000,
            'unlock_block' => $data['unlock_block'] ?? 5,
            'words_per_page' => $data['words_per_page'] ?? config('kpbook.words_per_page'),
            'status' => 'draft',
        ]);

        $this->attachSource($book, $request->file('file'));

        return response()->json(['data' => $this->present($book->refresh())], 201);
    }

    /** Change price, preview pages, minimum payment and so on. Page count is set by the import only. */
    public function update(Request $request, Book $book)
    {
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:200'],
            'subtitle' => ['sometimes', 'nullable', 'string', 'max:200'],
            'author' => ['sometimes', 'string', 'max:200'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'price_full' => ['sometimes', 'integer', 'min:1000'],
            'free_preview_pages' => ['sometimes', 'integer', 'min:0', 'max:50'],
            'min_payment' => ['sometimes', 'integer', 'min:100'],
            'unlock_block' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        if (isset($data['free_preview_pages']) && $book->total_pages > 0) {
            $data['free_preview_pages'] = min($data['free_preview_pages'], $book->total_pages);
        }

        $book->update($data);

        return response()->json(['data' => $this->present($book->refresh())]);
    }

    /**
     * Upload a replacement file, or re-split the stored file with a different words_per_page.
     * Refused once readers have paid, because changing the page count would change what they unlocked.
     */
    public function import(Request $request, Book $book)
    {
        if ($book->accesses()->exists()) {
            return response()->json([
                'message' => 'Readers have already paid for this book, so its pages cannot be rebuilt. Fix single pages instead.',
                'code' => 'BOOK_HAS_READERS',
            ], 409);
        }

        $data = $request->validate([
            'file' => $this->fileRules(false),
            'words_per_page' => ['nullable', 'integer', 'min:100', 'max:600'],
        ]);

        if (! empty($data['words_per_page'])) {
            $book->update(['words_per_page' => $data['words_per_page']]);
        }

        if ($request->hasFile('file')) {
            $this->attachSource($book, $request->file('file'));
        } elseif ($book->source_path) {
            $book->update(['import_status' => 'queued', 'import_error' => null]);
            ImportBookJob::dispatch($book->id);
        } else {
            return response()->json(['message' => 'Upload a file first.'], 422);
        }

        return response()->json(['data' => $this->present($book->refresh())], 202);
    }

    public function publish(Book $book)
    {
        if ($book->import_status !== 'done' || $book->total_pages < 1) {
            return response()->json(['message' => 'The book file has not been imported successfully yet.'], 422);
        }

        $book->update(['status' => 'published', 'published_at' => $book->published_at ?? now()]);

        return response()->json(['data' => $this->present($book->refresh())]);
    }

    public function unpublish(Book $book)
    {
        $book->update(['status' => 'draft']);

        return response()->json(['data' => $this->present($book->refresh())]);
    }

    /** Upload or replace the cover image for a book. */
    public function cover(Request $request, Book $book)
    {
        $data = $request->validate([
            'cover' => ['required', 'file', 'image', 'max:' . ((int) config('kpbook.max_upload_mb') * 1024)],
        ]);

        $file = $data['cover'];
        $disk = Storage::disk('public');
        $stored = $disk->putFileAs('book-covers', $file, Str::uuid() . '.' . $file->getClientOriginalExtension());

        if ($book->cover_path && str_starts_with($book->cover_path, 'book-covers/')) {
            $disk->delete($book->cover_path);
        }

        $book->update(['cover_path' => $stored]);

        return response()->json(['data' => $this->present($book->refresh())]);
    }

    /** Admin preview of any page, including locked ones. */
    public function showPage(Book $book, int $page)
    {
        $row = BookPage::where('book_id', $book->id)->where('page_no', $page)->firstOrFail();

        return response()->json(['data' => ['page' => $row->page_no, 'content' => $row->content]]);
    }

    /** Fix a typo on one page. Does not change the page count. */
    public function updatePage(Request $request, Book $book, int $page)
    {
        $data = $request->validate(['content' => ['required', 'string', 'max:20000']]);

        $row = BookPage::where('book_id', $book->id)->where('page_no', $page)->firstOrFail();
        $row->update(['content' => $data['content']]);

        return response()->json(['data' => ['page' => $row->page_no, 'content' => $row->content]]);
    }

    private function fileRules(bool $required): array
    {
        return [
            $required ? 'required' : 'nullable',
            'file',
            'max:' . ((int) config('kpbook.max_upload_mb') * 1024),
            'extensions:' . self::FORMATS,
        ];
    }

    private function attachSource(Book $book, UploadedFile $file): void
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $disk = Storage::disk(config('kpbook.source_disk'));

        $stored = $disk->putFileAs('book-sources', $file, Str::uuid() . '.' . $ext);

        if ($book->source_path && $book->source_path !== $stored) {
            $disk->delete($book->source_path);
        }

        $book->update([
            'source_path' => $stored,
            'source_format' => $ext,
            'import_status' => 'queued',
            'import_error' => null,
        ]);

        ImportBookJob::dispatch($book->id);
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'book';
        $slug = $base;
        $i = 2;

        while (Book::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    private function present(Book $b): array
    {
        return [
            'id' => $b->id,
            'slug' => $b->slug,
            'title' => $b->title,
            'subtitle' => $b->subtitle,
            'author' => $b->author,
            'description' => $b->description,
            'cover_path' => $b->cover_path,
            'cover_url' => $b->cover_path ? asset('storage/' . $b->cover_path) : null,
            'status' => $b->status,
            'price_full' => $b->price_full,
            'total_pages' => $b->total_pages,
            'free_preview_pages' => $b->free_preview_pages,
            'min_payment' => $b->min_payment,
            'unlock_block' => $b->unlock_block,
            'words_per_page' => $b->words_per_page,
            'source_format' => $b->source_format,
            'import_status' => $b->import_status,
            'import_error' => $b->import_error,
            'readers' => $b->accesses_count ?? null,
            'revenue' => isset($b->revenue) ? (int) $b->revenue : null,
        ];
    }
}
