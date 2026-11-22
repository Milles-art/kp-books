# Backend

The frontend needs a backend for two things:

1. **TanStack server functions** — `GET/POST /_serverFn/<hash>` (16 functions)
2. **Convex** — the live data/sync service at `sleek-bat-361.convex.cloud`

This folder implements #1 locally so the frontend works without contacting the original app.
#2 remains an external hosted service; its URL is configured in `config.js` and is also
compiled into the frontend bundle (`site/assets/index-BVLawKKe.js`).

## Files

- `config.js` — port, Convex URL, upstream URL, proxy switch.
- `server-functions.json` — captured responses for all 16 server-function IDs.
- `server-functions.js` — request handler; read endpoints return the captured payload,
  write/auth endpoints return a neutral success result.

## Run

From the project root:

```bash
node server.js
```

Optional — forward server functions to the original app instead of answering locally:

```bash
PROXY_SERVERFN=1 node server.js
```

## Note on auth

The 6 POST endpoints (sign-in, sign-up, OAuth callback, token exchange, etc.) are stubbed
with a successful empty result. The demo app is used signed-out, so navigation, browsing,
filters, search, saves and the reader all work; real login would require connecting an
identity provider.
