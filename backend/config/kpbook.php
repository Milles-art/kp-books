<?php

return [
    // When true, the simulated gateway can confirm payments (demo and local only).
    'simulate_payments' => env('KPBOOK_SIMULATE_PAYMENTS', false),
    'default_gateway' => env('KPBOOK_GATEWAY', 'simulated'),
    'currency' => 'TZS',

    // Anti-sharing: soft device limit. Older sessions are revoked, not hard-deleted.
    'max_reading_devices' => env('KPBOOK_MAX_READING_DEVICES', 3),

    // Refunds: only confirmed payments inside this window can be refunded.
    'refund_window_hours' => env('KPBOOK_REFUND_WINDOW_HOURS', 24),

    // Signed entitlements let the other Kipanya apps trust a purchase without
    // calling this service on every request. Share this secret across apps.
    'entitlement_secret' => env('KPBOOK_ENTITLEMENT_SECRET'),
    'entitlement_ttl_minutes' => env('KPBOOK_ENTITLEMENT_TTL_MINUTES', 15),

    // Shared commerce: HMAC secret used to verify provider webhooks.
    'webhook_secret' => env('KPBOOK_WEBHOOK_SECRET'),

    // Page-view audit trail used to trace leaked screenshots.
    'page_audit_retention_days' => env('KPBOOK_PAGE_AUDIT_RETENTION_DAYS', 90),

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
