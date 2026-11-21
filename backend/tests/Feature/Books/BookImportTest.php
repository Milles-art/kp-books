<?php

namespace Tests\Feature\Books;

use App\Models\Book;
use App\Models\BookPage;
use App\Services\Books\BookImporter;
use App\Services\Books\BookPageSplitter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookImportTest extends TestCase
{
    use RefreshDatabase;

    private function words(int $n): string
    {
        return trim(str_repeat('word ', $n));
    }

    private function zipBytes(array $files): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'kp');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();
        $bytes = file_get_contents($tmp);
        unlink($tmp);

        return $bytes;
    }

    private function bookWithFile(string $ext, string $bytes): Book
    {
        Storage::fake('local');
        config(['kpbook.source_disk' => 'local']);

        $book = Book::factory()->create([
            'status' => 'draft',
            'total_pages' => 0,
            'source_format' => $ext,
            'source_path' => "book-sources/test.{$ext}",
            'import_status' => 'queued',
        ]);
        Storage::disk('local')->put($book->source_path, $bytes);

        return $book;
    }

    public function test_splitter_makes_pages_of_about_the_target_size(): void
    {
        $blocks = array_fill(0, 3, ['t' => 'p', 'x' => $this->words(100)]);

        $pages = (new BookPageSplitter())->split($blocks, 150);

        $this->assertCount(3, $pages);
    }

    public function test_splitter_starts_a_chapter_on_a_new_page(): void
    {
        $blocks = [
            ['t' => 'p', 'x' => $this->words(120)],
            ['t' => 'h', 'x' => 'Chapter 2'],
            ['t' => 'p', 'x' => $this->words(30)],
        ];

        $pages = (new BookPageSplitter())->split($blocks, 200);

        $this->assertCount(2, $pages);
        $this->assertStringStartsWith('## Chapter 2', $pages[1]);
    }

    public function test_splitter_breaks_a_very_long_paragraph_by_sentence(): void
    {
        $long = implode(' ', array_fill(0, 40, 'This sentence has exactly seven words.'));

        $pages = (new BookPageSplitter())->split([['t' => 'p', 'x' => $long]], 100);

        $this->assertGreaterThan(2, count($pages));
    }

    public function test_text_file_is_imported_into_pages(): void
    {
        $paras = array_map(fn ($i) => "Paragraph {$i} " . $this->words(60), range(1, 40));
        $book = $this->bookWithFile('txt', implode("\n\n", $paras));

        $this->assertTrue(app(BookImporter::class)->import($book));

        $book->refresh();
        $this->assertSame('done', $book->import_status);
        $this->assertGreaterThan(1, $book->total_pages);
        $this->assertSame($book->total_pages, BookPage::where('book_id', $book->id)->count());
    }

    public function test_docx_is_imported_with_headings(): void
    {
        $xml = '<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
            . '<w:p><w:pPr><w:pStyle w:val="Heading1"/></w:pPr><w:r><w:t>Chapter 1</w:t></w:r></w:p>'
            . '<w:p><w:r><w:t>First paragraph of the book.</w:t></w:r></w:p>'
            . '</w:body></w:document>';
        $book = $this->bookWithFile('docx', $this->zipBytes(['word/document.xml' => $xml]));

        $this->assertTrue(app(BookImporter::class)->import($book));

        $content = BookPage::where('book_id', $book->id)->where('page_no', 1)->value('content');
        $this->assertStringContainsString('## Chapter 1', $content);
        $this->assertStringContainsString('First paragraph of the book.', $content);
    }

    public function test_epub_is_imported_in_reading_order(): void
    {
        $book = $this->bookWithFile('epub', $this->zipBytes([
            'META-INF/container.xml' => '<container><rootfiles><rootfile full-path="OEBPS/content.opf"/></rootfiles></container>',
            'OEBPS/content.opf' => '<package xmlns="http://www.idpf.org/2007/opf"><manifest>'
                . '<item id="c1" href="ch1.xhtml" media-type="application/xhtml+xml"/></manifest>'
                . '<spine><itemref idref="c1"/></spine></package>',
            'OEBPS/ch1.xhtml' => '<html><body><h1>One</h1><p>Hello reader.</p></body></html>',
        ]));

        $this->assertTrue(app(BookImporter::class)->import($book));

        $content = BookPage::where('book_id', $book->id)->where('page_no', 1)->value('content');
        $this->assertStringContainsString('## One', $content);
        $this->assertStringContainsString('Hello reader.', $content);
    }

    public function test_unsupported_file_type_fails_with_a_clear_message(): void
    {
        $book = $this->bookWithFile('xyz', 'whatever');

        $this->assertFalse(app(BookImporter::class)->import($book));

        $book->refresh();
        $this->assertSame('failed', $book->import_status);
        $this->assertStringContainsString('not supported', $book->import_error);
    }

    public function test_file_with_no_text_fails_instead_of_creating_an_empty_book(): void
    {
        $book = $this->bookWithFile('txt', "   \n\n  ");

        $this->assertFalse(app(BookImporter::class)->import($book));
        $this->assertSame('failed', $book->fresh()->import_status);
    }
}
