# KP Books — Project Structure

## Backend (Laravel)

- `app/Http/Controllers/Api/V1/` — versioned API controllers (Auth, Content, Books, Library, Admin)
- `app/Models/` — Eloquent models
- `app/Services/Books/` — book access, unlock, and purchase logic
- `database/migrations/` — schema (books, book_pages, user_books, book_payments, book_wishlists)
- `routes/` — api.php, api_content.php, api_admin.php, web.php
- `tests/Feature/` — feature tests by domain

## Frontend (React)

- `src/api/` — API client and endpoints
- `src/components/` — reusable UI and domain components
- `src/pages/` — route-level pages (books, library, auth, admin)
- `src/layouts/` — app shells
- `src/routes/` — router configuration
- `src/store/` — state management
- `src/styles/` — global styles

## Non-goals

No self-hosted video, no fan-art gallery. Digital books only — no downloads, no shareable files.
EOF