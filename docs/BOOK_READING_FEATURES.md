# Kipanya Book — online reading & pay-as-you-read features

This document covers the features added after the base book-reading implementation.
**Offline reading is deliberately not implemented** — Kipanya decided to leave it out.
Books remain online-only: pages are served one at a time, never as a file or download.

## What is implemented

### Wishlist & collections (server-side)
- `wishlist_items`, `book_collections`, `book_collection_items` tables
- `GET /api/v1/books/saved` — saved books + collections with their books
- `POST /api/v1/books/{slug}/save` — toggle saved
- `POST /api/v1/books/collections`, `PATCH|DELETE /books/collections/{id}`
- `POST|DELETE /books/collections/{id}/books/{slug}`
- UI: `/saved` page, Save button on book detail

### Author pages
- `authors` table, `books.author_id` (backfilled from the old `author` string)
- `GET /api/v1/authors`, `GET /api/v1/authors/{slug}`
- Admin: `GET|POST /api/v1/admin/authors`, `GET|PATCH|DELETE /authors/{slug}`, portrait upload
- UI: `/authors`, `/authors/:slug`

### Anti-sharing / device limits
- `reading_sessions` table; soft limit configured by `KPBOOK_MAX_READING_DEVICES` (default 3)
- Every page request registers/refreshes a device (`X-Device-Id` header, shared in `kp-shared`)
- Over the limit, the least recently used sessions are marked revoked — no hard lockout
- `GET /api/v1/books/devices`, `DELETE /api/v1/books/devices/{id}`
- UI: `/devices`
- `throttle:book-pages` still applies to every page request

### Hardened watermarking + audit
- Every served page gets a watermark containing masked identity + request ID + date
- `book_page_views` audit row per served page (user, book, page, device, IP, time)
- Responses send `Cache-Control: no-store, private`, `X-Content-Type-Options: nosniff`,
  `Referrer-Policy: no-referrer`
- Locked pages still return 403 **before** any content is read from storage

### Refunds (partial payments)
- `refunds` table, one per payment; `BookPayment::REFUNDED` status
- Eligibility: confirmed payments only, inside `KPBOOK_REFUND_WINDOW_HOURS` (default 24h)
- `POST /api/v1/payments/{payment}/refund/request`
- Admin: `GET /api/v1/admin/refunds`, `POST /api/v1/admin/refunds/{id}/process` with `approve`
- Approving recomputes access from remaining confirmed payments; unlocked pages never
  fall below the book's free preview
- **Policy still needs Kipanya sign-off** (refund window, whether pages are revoked)

### Shared commerce / entitlements
- `GET /api/v1/books/{slug}/entitlement` returns a short-lived HMAC-signed entitlement
  (reader, book, allowed pages, device, expiry) for the other Kipanya apps
- `KPBOOK_ENTITLEMENT_SECRET` must be shared across apps; fallback is `APP_KEY`
- `KPBOOK_WEBHOOK_SECRET` enables HMAC verification (`X-Signature`) of provider callbacks
- Full shared-commerce integration still requires the other apps to consume entitlements

## Environment variables

| Variable | Default | Purpose |
|---|---|---|
| `KPBOOK_MAX_READING_DEVICES` | `3` | Soft device limit |
| `KPBOOK_REFUND_WINDOW_HOURS` | `24` | Refund eligibility window |
| `KPBOOK_ENTITLEMENT_SECRET` | `APP_KEY` | Signs cross-app entitlements |
| `KPBOOK_ENTITLEMENT_TTL_MINUTES` | `15` | Entitlement lifetime |
| `KPBOOK_WEBHOOK_SECRET` | unset | Verifies provider webhooks |
| `KPBOOK_PAGE_AUDIT_RETENTION_DAYS` | `90` | Audit retention guidance |

## Tests
`php artisan test` — 33 tests pass, including new coverage for wishlist/collections,
author CRUD, device limits, page security/watermark, refunds and entitlements.
