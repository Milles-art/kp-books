<?php

namespace App\Services\Books\Import\Extractors;

use App\Services\Books\Import\BookTextExtractor;
use App\Services\Books\Import\ImportException;
use App\Services\Books\Import\TextNormalizer;
use App\Services\Books\Import\ZipGuard;

class DocxExtractor implements BookTextExtractor
{
    private const NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    public function extensions(): array
    {
        return ['docx'];
    }

    public function extract(string $path): array
    {
        $zip = ZipGuard::open($path, 'Word');
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        $dom = $xml === false ? null : ZipGuard::dom($xml);
        if (! $dom) {
            throw new ImportException('This Word file has no readable content.');
        }

        $xp = new \DOMXPath($dom);
        $xp->registerNamespace('w', self::NS);

        $out = [];
        foreach ($xp->query('//w:body//w:p') as $p) {
            $text = '';
            foreach ($xp->query('.//w:t|.//w:tab|.//w:br', $p) as $n) {
                $text .= $n->localName === 't' ? $n->textContent : ' ';
            }

            $text = TextNormalizer::line($text);
            if ($text === '') {
                continue;
            }

            $style = (string) $xp->evaluate('string(./w:pPr/w:pStyle/@w:val)', $p);
            $out[] = [
                't' => preg_match('/^(heading|title|kichwa)/i', $style) === 1 ? 'h' : 'p',
                'x' => $text,
            ];
        }

        return $out;
    }
}
