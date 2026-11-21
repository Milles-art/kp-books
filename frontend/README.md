# KP Book web (React + Vite)

Reader-side screens for KP Book, calling the Laravel API in kp-book-api.

## Run

1. `cp .env.example .env` and set `VITE_API_URL` (the Laravel server). To test without the login screen, put a Sanctum token in `VITE_DEV_TOKEN`.
2. `npm install`
3. `npm run dev`

## Screens

| Route | Screen |
|---|---|
| `/` | Library with search |
| `/books/:slug` | Book page: details, "what your payment opens", pay and share buttons |
| `/read/:slug/:page` | Reader: one page at a time, lock screen, font size, watermark, resume |
| `/my-library` | Books the reader has paid for or started |

Plus the pay-any-amount modal: quick amounts, live "this opens pages 1 to N" preview from the API, provider choice.

## Connecting to the rest of KP Wear

- Login: the app reads the Sanctum token from `localStorage` under `VITE_TOKEN_KEY` and sends it as a Bearer token. Set the key to whatever KP Wear's login uses, and `VITE_LOGIN_URL` to its login page. No login screen is built here.
- To move into the KP Wear app, copy `src/pages`, `src/components`, `src/lib/api.js` and add the four routes to its router.
- Real payments: set `VITE_SIMULATE_PAYMENTS=false`. The modal then starts the payment and waits for the provider's confirmation (it checks every 3 seconds for up to 2 minutes).

## Notes

- Pages arrive as plain text and are drawn as text, never as HTML. A line starting with `## ` is a chapter heading.
- Copy blocking, no right-click and the watermark only deter casual sharing. The real protection is that locked pages never leave the server.
- Styling is plain CSS in `src/styles.css` with the KP green accent, light and dark by device setting. Swap it for the KP Wear design tokens when you have them.
