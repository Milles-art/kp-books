<?php

namespace App\Services\Books\Import\Extractors;

use App\Services\Books\Import\BookTextExtractor;
use App\Services\Books\Import\DomBlocks;
use App\Services\Books\Import\ImportException;
use App\Services\Books\Import\ZipGuard;

class EpubExtractor implements BookTextExtractor
{
    public function extensions(): array
    {
        return ['epub'];
    }

    public function extract(string $path): array
    {
        $zip = ZipGuard::open($path, 'EPUB');

        $container = $zip->getFromName('META-INF/container.xml');
        if ($container === false || ! preg_match('/full-path="([^"]+)"/', $container, $m)) {
            throw new ImportException('This EPUB is missing its table of contents file.');
        }

        $opfPath = $m[1];
        $opfXml = $zip->getFromName($opfPath);
        $opf = $opfXml === false ? null : ZipGuard::dom($opfXml);
        if (! $opf) {
            throw new ImportException('This EPUB has a damaged package file.');
        }

        $xp = new \DOMXPath($opf);

        $manifest = [];
        foreach ($xp->query('//*[local-name()="item"]') as $item) {
            $manifest[$item->getAttribute('id')] = [
                'href' => $item->getAttribute('href'),
                'type' => $item->getAttribute('media-type'),
                'props' => $item->getAttribute('properties'),
            ];
        }

        $base = dirname($opfPath);
        $out = [];

        foreach ($xp->query('//*[local-name()="itemref"]') as $ref) {
            $item = $manifest[$ref->getAttribute('idref')] ?? null;
            if (! $item || ! str_contains($item['type'], 'html') || str_contains($item['props'], 'nav')) {
                continue;
            }

            $href = rawurldecode(explode('#', $item['href'])[0]);
            $full = $this->normalize(($base === '.' || $base === '') ? $href : $base . '/' . $href);
            $html = $zip->getFromName($full);

            if ($html !== false) {
                $out = array_merge($out, DomBlocks::fromHtml($html));
            }
        }

        $zip->close();

        return $out;
    }

    private function normalize(string $path): string
    {
        $parts = [];
        foreach (explode('/', $path) as $seg) {
            if ($seg === '' || $seg === '.') {
                continue;
            }
            $seg === '..' ? array_pop($parts) : $parts[] = $seg;
        }

        return implode('/', $parts);
    }
}
