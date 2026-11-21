# Shared KP frontend layer

Reusable modules shared between KP Books and KP Wear.

## What's here
- `ui/Icon.jsx` — icon set
- `layout/Footer.jsx` — site footer (shared by both apps)
- `ui/BookCover.jsx` — placeholder cover
- `lib/api.js` — API client (Bearer token, error shape, money)
- `styles/tokens.css` — design tokens (colors, fonts, dark mode)

## Using in KP Wear
Copy this folder into KP Wear's `src/shared/` and import from there, or publish as an npm workspace package (`@kp/shared`) and depend on it from both apps. The tokens should be the source of truth for both apps' styling.

## What stays app-specific
Catalog cards, cart/checkout, reader, hero sections, category browsing.