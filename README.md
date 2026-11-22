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

## Static storefront mirror + admin console

A self-contained, page-for-page mirror of the public KP Book storefront, merged into this
repo. It is independent of the Laravel backend and React app above.

- `site/` — the static site: 21 HTML pages, bundled JS/CSS, local fonts and cover images
- `site/admin/` — admin console (Overview, Books, Authors, Orders, Readers, Settings)
- `server.js` — Node server serving the site, admin API and the frontend server functions
- `backend/admin-api.cjs`, `backend/server-functions.cjs`, `backend/data/` — backend pieces
- `backend/NODE_ADMIN.md` — admin API notes
- `docs/STATIC_SITE.md` — full details, credentials and regeneration scripts

Run it from the repo root:

```bash
node server.js                 # http://localhost:4175
# admin: http://localhost:4175/admin  (admin / admin123)
```
