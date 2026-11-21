<?php

namespace App\Services\Books\Import;

/** Safety checks for zip-based formats (EPUB, DOCX). Entries are read in memory, never extracted to disk. */
final class ZipGuard
{
    public static function open(string $path, string $label): \ZipArchive
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new ImportException("This {$label} file could not be opened. It may be damaged.");
        }

        $total = 0;
        if ($zip->numFiles > 5000) {
            throw new ImportException("This {$label} file has too many parts.");
        }
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $total += (int) ($zip->statIndex($i)['size'] ?? 0);
        }
        if ($total > 400 * 1024 * 1024) {
            throw new ImportException("This {$label} file is too large when unpacked.");
        }

        return $zip;
    }

    /** Parse XML without loading external entities or network resources. */
    public static function dom(string $xml): ?\DOMDocument
    {
        $dom = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $ok = $dom->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        return $ok ? $dom : null;
    }
}
