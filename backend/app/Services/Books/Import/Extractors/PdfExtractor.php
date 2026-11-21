<?php

namespace App\Services\Books\Import\Extractors;

use App\Services\Books\Import\BookTextExtractor;
use App\Services\Books\Import\ImportException;
use App\Services\Books\Import\RunsTools;
use App\Services\Books\Import\TextNormalizer;

/** Text PDFs through pdftotext. Scanned PDFs automatically go through OCR. */
class PdfExtractor implements BookTextExtractor
{
    use RunsTools;

    public function extensions(): array
    {
        return ['pdf'];
    }

    public function extract(string $path): array
    {
        $pdftotext = $this->tool('pdftotext', 'poppler-utils (pdftotext)');
        $text = $this->run([$pdftotext, '-enc', 'UTF-8', '-nopgbrk', $path, '-'], 180);

        $pages = $this->pageCount($path);
        $chars = mb_strlen(preg_replace('/\s+/u', '', $text) ?? '');
        $min = (int) config('kpbook.ocr_min_chars_per_page', 40);

        $scanned = $pages > 0 ? ($chars / $pages) < $min : $chars < $min;
        if ($scanned) {
            $text = $this->ocr($path);
        }

        return TextNormalizer::blocks($text);
    }

    private function pageCount(string $path): int
    {
        if (! $this->toolExists('pdfinfo')) {
            return 0;
        }

        try {
            $info = $this->run([$this->tool('pdfinfo', 'poppler-utils'), $path], 60);
        } catch (ImportException) {
            return 0;
        }

        return preg_match('/^Pages:\s+(\d+)/m', $info, $m) ? (int) $m[1] : 0;
    }

    /** Read a scanned PDF by turning each page into an image and running Tesseract. */
    private function ocr(string $path): string
    {
        if (! $this->toolExists('tesseract') || ! $this->toolExists('pdftoppm')) {
            throw new ImportException(
                'This PDF is scanned pages (pictures of text). The server needs tesseract-ocr and poppler-utils to read it, '
                . 'or upload a Word, EPUB or text-based PDF instead.'
            );
        }

        $dir = $this->tempDir();

        try {
            $this->run([$this->tool('pdftoppm', 'poppler-utils'), '-r', '200', '-png', $path, $dir . '/p'], 1800);

            $files = glob($dir . '/p*.png') ?: [];
            natsort($files);

            $pages = [];
            foreach ($files as $png) {
                $pages[] = $this->run([
                    $this->tool('tesseract', 'tesseract-ocr'), $png, 'stdout',
                    '-l', (string) config('kpbook.ocr_languages', 'eng'),
                ], 180);
            }

            return implode("\n\n", $pages);
        } finally {
            $this->removeDir($dir);
        }
    }
}
