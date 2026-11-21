# KP Books

Kipanya Books (KP Books) — digital book platform for the Kipanya ecosystem.

- Backend: Laravel (API-first, MySQL)
- Frontend: React (web app)
- Reference storefront: https://kipanya.bizismart.com/ (KP Wear)

## Structure

- `backend/` — Laravel application (API, models, migrations, tests)
- `frontend/` — React web application
- `docs/` — planning, ERD, sitemap, API contracts

## Scope (from Kipanya Master System Blueprint)

Digital-only books. Pay-any-amount access (minimum 1,000 TZS). Pages unlock in order from the start. Locked pages are never sent to the client; the server checks access for every page request. My Library tracks unlocked pages and reading progress. Admin configures full price, free sample length, minimum payment, and unlock block size per book.

Status: scaffolding only — no implementation yet.
