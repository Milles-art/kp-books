# KP Book — local recreation

A faithful, page-for-page recreation of <https://kipanya-digital-books-552698.onhercules.app/>.

## Admin side

Open **<http://localhost:4175/admin>** and sign in with `admin` / `admin123`
(override with `ADMIN_USER` / `ADMIN_PASS` environment variables).

The admin console has six sections:
- **Overview** — stats, catalogue summary and activity log
- **Books** — add/edit/delete books, change prices, minimum payment, sample pages, block size
- **Authors** — add/edit/delete authors and bios
- **Orders** — demo payments, status changes, and "Import from ledger" which pulls real
  payments made in the storefront (`kp-book:ledger:v1`) into the admin
- **Readers** — manage demo reader accounts
- **Settings** — site-wide config

Data lives in `backend/data/admin.json`; the API is in `backend/admin-api.js`.

## Run it

```bash
cd "/home/millen/Documents/Default Project/kp-book"
node server.js
```

Then open **<http://localhost:4175>** (or set `PORT=xxxx` to use another port).

## What was done

- Mirrored the built React/TanStack Start app: all JS chunks, CSS, favicon, fonts and
  cover images from the original site.
- Saved the server-rendered HTML for **every route**, so direct URL loads look exactly
  like the original — including the home page, books, authors, library, saved, and all
  book/author detail pages.
- Rewrote CDN image URLs to local copies, so all covers load from this folder.
- The small Node server serves the static files and proxies TanStack server-function
  calls to the live app, which keeps client-side navigation, theme switching, filters,
  search, save/bookmark buttons and the reader working exactly as on the original.

## Pages included

| Route | Page |
| --- | --- |
| `/` | Home |
| `/books` | All books (filter + search) |
| `/authors` | Authors |
| `/library` | My Library |
| `/wishlist` | Saved |
| `/books/mtaa-wa-kazi` | Mtaa wa Kazi |
| `/books/the-salt-road` | The Salt Road |
| `/books/konfidence` | Konfidence |
| `/books/build-better` | Build Better |
| `/books/kiboko-na-mvua` | Kiboko na Mvua |
| `/books/kilimanjaro-mornings` | Kilimanjaro Mornings |
| `/read/mtaa-wa-kazi` | Reader — Mtaa wa Kazi |
| `/read/the-salt-road` | Reader — The Salt Road |
| `/read/konfidence` | Reader — Konfidence |
| `/read/build-better` | Reader — Build Better |
| `/read/kiboko-na-mvua` | Reader — Kiboko na Mvua |
| `/read/kilimanjaro-mornings` | Reader — Kilimanjaro Mornings |
| `/authors/masoud-kipanya` | Masoud Kipanya |
| `/authors/zawadi-mrisho` | Zawadi Mrisho |
| `/authors/juma-kassim` | Juma Kassim |

## Folder layout

- `site/` — the served site (HTML pages, `assets/`, `_hercules/`, local cover images)
- `_ssr/` — the untouched SSR HTML captured for each route
- `mirror.py` — re-downloads all assets from the live site
- `assemble.py` — rebuilds `site/` from the captured HTML and assets
- `compress_images.py` — converts the original PNG covers to WebP (~19 MB → ~1.7 MB) and rewrites references; run it after `assemble.py`
- `verify.py` — checks that all pages and referenced assets exist
- `backend/` — local backend: config, all 16 server-function handlers, captured responses, admin API and JSON data store
- `server.js` — static server + backend routing (proxy mode available via `PROXY_SERVERFN=1`)

Zips:
- `kp-book-frontend.zip` — frontend only (`site/`), compressed WebP images
- `kp-book-full.zip` — frontend + backend (`site/`, `backend/`, `server.js`, this README)

> The 16 server functions now run locally, so no proxy is needed. Internet access is
> only needed for the external Convex connection at `sleek-bat-361.convex.cloud`.
> Everything visual (fonts, CSS, images, JS) is local.
