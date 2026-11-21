<?php

namespace App\Services\Books\Import\Extractors;

use App\Services\Books\Import\BookTextExtractor;
use App\Services\Books\Import\ImportException;
use App\Services\Books\Import\RunsTools;

/** Old and other word processor formats: converted to DOCX by LibreOffice, then read as DOCX. */
class LibreOfficeExtractor implements BookTextExtractor
{
    use RunsTools;

    public function extensions(): array
    {
        return ['doc', 'rtf', 'odt', 'wps'];
    }

    public function extract(string $path): array
    {
        $soffice = $this->tool('soffice', 'LibreOffice');
        $dir = $this->tempDir();

        try {
            $this->run([
                $soffice, '--headless', '-env:UserInstallation=file://' . $dir . '/profile',
                '--convert-to', 'docx', '--outdir', $dir, $path,
            ], 240);

            $converted = $dir . '/' . pathinfo($path, PATHINFO_FILENAME) . '.docx';
            if (! is_file($converted)) {
                throw new ImportException('The document could not be converted. Try saving it as DOCX or PDF.');
            }

            return (new DocxExtractor())->extract($converted);
        } finally {
            $this->removeDir($dir);
        }
    }
}
