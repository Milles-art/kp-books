#!/usr/bin/env node
// KP Book server: static frontend + locally implemented backend routes.
const http = require("http");
const fs = require("fs");
const path = require("path");
const config = require("./backend/config");
const serverFns = require("./backend/server-functions");
const adminApi = require("./backend/admin-api");

const ROOT = config.siteDir;
const PORT = config.port;

const MIME = {
  ".html": "text/html; charset=utf-8",
  ".js": "text/javascript; charset=utf-8",
  ".css": "text/css; charset=utf-8",
  ".json": "application/json; charset=utf-8",
  ".ico": "image/x-icon",
  ".png": "image/png",
  ".jpg": "image/jpeg",
  ".jpeg": "image/jpeg",
  ".webp": "image/webp",
  ".svg": "image/svg+xml",
  ".woff": "font/woff",
  ".woff2": "font/woff2",
  ".ttf": "font/ttf",
};

const server = http.createServer((req, res) => {
  let urlPath;
  try {
    urlPath = new URL(req.url, "http://localhost").pathname;
  } catch {
    res.writeHead(400).end("Bad request");
    return;
  }

  // Hercules telemetry stub.
  if (urlPath === "/_hercules/i") {
    res.writeHead(200, { "Content-Type": "application/json" }).end("{}");
    return;
  }

  // Admin REST API.
  if (urlPath.startsWith("/api/admin/")) {
    adminApi.handle(req, res, new URL(req.url, "http://localhost"));
    return;
  }

  // Backend: the 16 TanStack server functions used by the frontend.
  if (urlPath.startsWith("/_serverFn/")) {
    serverFns.handle(urlPath.slice("/_serverFn/".length), req, res);
    return;
  }

  let decoded;
  try {
    decoded = decodeURIComponent(urlPath);
  } catch {
    decoded = urlPath;
  }
  const safe = path.normalize(decoded).replace(/^(\.\.(\/|\\|$))+/, "");
  const filePath = path.join(ROOT, safe);

  const serve = (p) => {
    fs.stat(p, (err, stat) => {
      if (err || !stat.isFile()) {
        res.writeHead(404, { "Content-Type": "text/plain" }).end("Not found");
        return;
      }
      const ext = path.extname(p).toLowerCase();
      const type = p.endsWith("/css2") ? "text/css; charset=utf-8" : MIME[ext] || "application/octet-stream";
      res.writeHead(200, {
        "Content-Type": type,
        "Cache-Control": "no-cache",
      });
      fs.createReadStream(p).pipe(res);
    });
  };

  fs.stat(filePath, (err, stat) => {
    if (!err && stat.isDirectory()) serve(path.join(filePath, "index.html"));
    else if (!err && stat.isFile()) serve(filePath);
    else serve(path.join(ROOT, safe, "index.html"));
  });
});

server.listen(PORT, () => {
  console.log(`KP Book running at http://localhost:${PORT}`);
  console.log(`Backend server functions: ${serverFns.ids.length} loaded (${config.proxyServerFns ? "proxy mode" : "local mode"})`);
});
