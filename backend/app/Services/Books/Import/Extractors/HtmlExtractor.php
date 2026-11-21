<?php

namespace App\Services\Books\Import\Extractors;

use App\Services\Books\Import\BookTextExtractor;
use App\Services\Books\Import\DomBlocks;
use App\Services\Books\Import\ImportException;

class HtmlExtractor implements BookTextExtractor
{
    public function extensions(): array
    {
        return ['html', 'htm'];
    }

    public function extract(string $path): array
    {
        $html = file_get_contents($path);
        if ($html === false) {
            throw new ImportException('The HTML file could not be read.');
        }

        return DomBlocks::fromHtml($html);
    }
}
