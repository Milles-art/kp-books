<?php

namespace App\Services\Books\Import;

final class TextNormalizer
{
    /** Clean raw text: unify line breaks, drop control characters, collapse spaces. */
    public static function clean(string $s): string
    {
        $s = str_replace(["\r\n", "\r", "\u{00A0}", "\u{200B}", "\u{FEFF}"], ["\n", "\n", ' ', '', ''], $s);
        $s = preg_replace('/[^\P{C}\n\t]/u', '', $s) ?? $s;
        $s = preg_replace('/[ \t]+/u', ' ', $s) ?? $s;

        if (class_exists(\Normalizer::class)) {
            $n = \Normalizer::normalize($s);
            if ($n !== false) {
                $s = $n;
            }
        }

        return trim($s);
    }

    /** One line of text with all whitespace collapsed. */
    public static function line(string $s): string
    {
        return trim(preg_replace('/\s+/u', ' ', self::clean($s)) ?? '');
    }

    /**
     * Turn raw text (blank lines between paragraphs, hard-wrapped lines inside them)
     * into blocks. Used for TXT, Markdown, PDF and OCR text.
     *
     * @return array<int, array{t: string, x: string}>
     */
    public static function blocks(string $raw): array
    {
        $out = [];

        foreach (preg_split('/\n{2,}/', self::clean($raw)) ?: [] as $para) {
            $para = preg_replace('/-\n(?=\p{Ll})/u', '', $para) ?? $para;      // join hyphenated line breaks
            $para = trim(preg_replace('/\s*\n\s*/u', ' ', $para) ?? $para);

            if ($para === '' || preg_match('/^\d{1,4}$/', $para)) {            // blank or bare page number
                continue;
            }

            if (preg_match('/^#{1,3}\s+(.+)$/u', $para, $m)) {                 // Markdown heading
                $out[] = ['t' => 'h', 'x' => trim($m[1])];
            } elseif (self::looksLikeChapter($para)) {
                $out[] = ['t' => 'h', 'x' => $para];
            } else {
                $out[] = ['t' => 'p', 'x' => $para];
            }
        }

        return $out;
    }

    private static function looksLikeChapter(string $p): bool
    {
        return mb_strlen($p) <= 80
            && preg_match('/^(chapter|part|sura|sehemu|mlango)(\s+(ya|wa))?\s+(\d+|[ivxlc]+)\b/iu', $p) === 1;
    }
}
