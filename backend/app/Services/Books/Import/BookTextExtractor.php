<?php

namespace App\Services\Books\Import;

interface BookTextExtractor
{
    /** @return string[] Lowercase file extensions this extractor reads. */
    public function extensions(): array;

    /**
     * Read the file and return its text as ordered blocks.
     * Each block is ['t' => 'h' (heading) | 'p' (paragraph), 'x' => plain text].
     *
     * @return array<int, array{t: string, x: string}>
     * @throws ImportException
     */
    public function extract(string $path): array;
}
