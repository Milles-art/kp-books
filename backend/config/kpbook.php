<?php

return [
    // When true, the simulated gateway can confirm payments (demo and local only).
    'simulate_payments' => env('KPBOOK_SIMULATE_PAYMENTS', false),
    'default_gateway' => env('KPBOOK_GATEWAY', 'simulated'),
    'currency' => 'TZS',

    // Book import. Original files are private and are never served to readers.
    'source_disk' => env('KPBOOK_SOURCE_DISK', 'local'),
    'max_upload_mb' => 50,
    'words_per_page' => 280,

    // Scanned PDFs: if a PDF has fewer text characters per page than this, run OCR.
    'ocr_min_chars_per_page' => 40,
    'ocr_languages' => env('KPBOOK_OCR_LANGS', 'eng+swa'),

    // Command line tools used by some formats (names or full paths).
    'tools' => [
        'pdftotext' => 'pdftotext',   // poppler-utils
        'pdfinfo' => 'pdfinfo',       // poppler-utils
        'pdftoppm' => 'pdftoppm',     // poppler-utils (only for OCR)
        'tesseract' => 'tesseract',   // tesseract-ocr (only for OCR)
        'soffice' => 'soffice',       // libreoffice (doc, rtf, odt, wps)
        'ebook_convert' => 'ebook-convert', // calibre (mobi, azw3, fb2)
    ],
];
