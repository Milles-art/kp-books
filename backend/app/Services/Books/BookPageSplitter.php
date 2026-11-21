<?php

namespace App\Services\Books;

/**
 * Cuts extracted blocks into reading pages of about N words.
 * Pages are plain text. Paragraphs are separated by a blank line and a heading line starts with "## ".
 * Render pages as text on the client, never as HTML.
 */
class BookPageSplitter
{
    /**
     * @param  array<int, array{t: string, x: string}>  $blocks
     * @return string[]  Page contents, page 1 first.
     */
    public function split(array $blocks, int $wordsPerPage = 280): array
    {
        $wordsPerPage = max(50, $wordsPerPage);
        $pages = [];
        $cur = [];
        $count = 0;

        $flush = function () use (&$pages, &$cur, &$count) {
            if ($cur) {
                $pages[] = implode("\n\n", $cur);
            }
            $cur = [];
            $count = 0;
        };

        foreach ($blocks as $block) {
            $text = trim($block['x']);
            if ($text === '') {
                continue;
            }

            $words = $this->words($text);

            if ($block['t'] === 'h') {
                if ($count >= $wordsPerPage * 0.3) {   // a new chapter starts on a fresh page
                    $flush();
                }
                $cur[] = '## ' . $text;
                $count += $words;
                continue;
            }

            if ($words > $wordsPerPage) {              // very long paragraph: split by sentence
                foreach ($this->sentences($text) as $sentence) {
                    $cur[] = $sentence;
                    $count += $this->words($sentence);
                    if ($count >= $wordsPerPage) {
                        $flush();
                    }
                }
                continue;
            }

            if ($count > 0 && $count + $words > $wordsPerPage * 1.15) {
                $flush();
            }

            $cur[] = $text;
            $count += $words;

            if ($count >= $wordsPerPage) {
                $flush();
            }
        }

        $flush();

        return $pages;
    }

    private function words(string $s): int
    {
        return count(preg_split('/\s+/u', trim($s), -1, PREG_SPLIT_NO_EMPTY) ?: []);
    }

    /** @return string[] */
    private function sentences(string $text): array
    {
        return preg_split('/(?<=[.!?…])\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [$text];
    }
}
