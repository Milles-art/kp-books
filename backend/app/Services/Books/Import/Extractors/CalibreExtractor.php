<?php

namespace App\Services\Books\Import\Extractors;

use App\Services\Books\Import\BookTextExtractor;
use App\Services\Books\Import\ImportException;
use App\Services\Books\Import\RunsTools;

/** Kindle and other e-book formats: converted to EPUB by Calibre, then read as EPUB. */
class CalibreExtractor implements BookTextExtractor
{
    use RunsTools;

    public function extensions(): array
    {
        return ['mobi', 'azw3', 'fb2'];
    }

    public function extract(string $path): array
    {
        $convert = $this->tool('ebook_convert', 'Calibre (ebook-convert)');
        $dir = $this->tempDir();

        try {
            $epub = $dir . '/book.epub';
            $this->run([$convert, $path, $epub], 300);

            if (! is_file($epub)) {
                throw new ImportException('The e-book could not be converted. Try uploading an EPUB or PDF.');
            }

            return (new EpubExtractor())->extract($epub);
        } finally {
            $this->removeDir($dir);
        }
    }
}
