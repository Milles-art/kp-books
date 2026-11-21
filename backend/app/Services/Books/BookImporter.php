<?php

namespace App\Services\Books;

use App\Models\Book;
use App\Models\BookPage;
use App\Services\Books\Import\BookTextExtractor;
use App\Services\Books\Import\Extractors\CalibreExtractor;
use App\Services\Books\Import\Extractors\DocxExtractor;
use App\Services\Books\Import\Extractors\EpubExtractor;
use App\Services\Books\Import\Extractors\HtmlExtractor;
use App\Services\Books\Import\Extractors\LibreOfficeExtractor;
use App\Services\Books\Import\Extractors\PdfExtractor;
use App\Services\Books\Import\Extractors\PlainTextExtractor;
use App\Services\Books\Import\ImportException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Reads a book's uploaded file, splits it into pages and saves them. */
class BookImporter
{
    /** @var BookTextExtractor[] */
    private array $extractors;

    public function __construct(private BookPageSplitter $splitter)
    {
        $this->extractors = [
            new PlainTextExtractor(), new HtmlExtractor(), new EpubExtractor(), new DocxExtractor(),
            new PdfExtractor(), new LibreOfficeExtractor(), new CalibreExtractor(),
        ];
    }

    /** @return string[] Every file extension the importer accepts. */
    public function supportedExtensions(): array
    {
        return array_merge(...array_map(fn ($e) => $e->extensions(), $this->extractors));
    }

    /** Returns true on success. On failure the reason is saved on the book for the admin to read. */
    public function import(Book $book): bool
    {
        $book->update(['import_status' => 'processing', 'import_error' => null]);

        try {
            $ext = strtolower((string) $book->source_format);
            $extractor = $this->extractorFor($ext);

            $disk = Storage::disk(config('kpbook.source_disk'));
            if (! $book->source_path || ! $disk->exists($book->source_path)) {
                throw new ImportException('The uploaded file is missing. Upload it again.');
            }

            $blocks = $extractor->extract($disk->path($book->source_path));
            $pages = $this->splitter->split($blocks, $book->words_per_page ?: (int) config('kpbook.words_per_page'));

            if (! $pages) {
                throw new ImportException('No readable text was found in the file.');
            }

            DB::transaction(function () use ($book, $pages) {
                BookPage::where('book_id', $book->id)->delete();

                $rows = [];
                foreach ($pages as $i => $content) {
                    $rows[] = ['book_id' => $book->id, 'page_no' => $i + 1, 'content' => $content];
                }
                foreach (array_chunk($rows, 200) as $chunk) {
                    BookPage::insert($chunk);
                }

                $book->update([
                    'total_pages' => count($pages),
                    'free_preview_pages' => min($book->free_preview_pages, count($pages)),
                    'import_status' => 'done',
                    'import_error' => null,
                ]);
            });

            return true;
        } catch (ImportException $e) {
            $book->update(['import_status' => 'failed', 'import_error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            report($e);
            $book->update(['import_status' => 'failed', 'import_error' => 'Something went wrong while reading the file.']);
        }

        return false;
    }

    private function extractorFor(string $ext): BookTextExtractor
    {
        foreach ($this->extractors as $extractor) {
            if (in_array($ext, $extractor->extensions(), true)) {
                return $extractor;
            }
        }

        throw new ImportException("Files of type \".{$ext}\" are not supported.");
    }
}
