<?php

namespace App\Services\Books\Import\Extractors;

use App\Services\Books\Import\BookTextExtractor;
use App\Services\Books\Import\ImportException;
use App\Services\Books\Import\TextNormalizer;

class PlainTextExtractor implements BookTextExtractor
{
    public function extensions(): array
    {
        return ['txt', 'md'];
    }

    public function extract(string $path): array
    {
        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new ImportException('The text file could not be read.');
        }

        $enc = mb_detect_encoding($raw, ['UTF-8', 'Windows-1252', 'ISO-8859-1'], true) ?: 'UTF-8';
        if ($enc !== 'UTF-8') {
            $raw = mb_convert_encoding($raw, 'UTF-8', $enc);
        }

        return TextNormalizer::blocks($raw);
    }
}
