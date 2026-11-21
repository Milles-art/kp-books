<?php

namespace App\Services\Books\Import;

/** Reads headings and paragraphs out of HTML or XHTML. */
final class DomBlocks
{
    /** @return array<int, array{t: string, x: string}> */
    public static function fromHtml(string $html): array
    {
        $html = preg_replace('/^\s*<\?xml[^>]*\?>/i', '', $html) ?? $html;

        $dom = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $xp = new \DOMXPath($dom);
        $out = [];

        foreach ($xp->query('//h1|//h2|//h3|//p|//li|//blockquote') as $node) {
            if (in_array($node->nodeName, ['li', 'blockquote'], true) && $xp->query('.//p', $node)->length > 0) {
                continue; // its paragraphs are read on their own
            }

            $text = TextNormalizer::line($node->textContent);
            if ($text === '') {
                continue;
            }

            $out[] = ['t' => in_array($node->nodeName, ['h1', 'h2', 'h3'], true) ? 'h' : 'p', 'x' => $text];
        }

        if (! $out) {
            $body = $xp->query('//body')->item(0);
            $out = TextNormalizer::blocks($body ? $body->textContent : '');
        }

        return $out;
    }
}
