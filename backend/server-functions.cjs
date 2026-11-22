#!/usr/bin/env node
// Local implementation of the 16 TanStack Start server functions the frontend calls.
// Read responses are captured verbatim from the original app; write/auth endpoints are
// stubbed with a successful "null" result (the demo app is used signed-out).
const fs = require("fs");
const path = require("path");
const https = require("https");
const config = require("./config.cjs");

const captured = JSON.parse(
  fs.readFileSync(path.join(__dirname, "server-functions.json"), "utf8")
);

// A seroval-encoded `null` result, matching the wire format the frontend expects.
const NULL_RESULT =
  '{"t":10,"i":0,"p":{"k":["result"],"v":[{"t":2,"s":0}]},"o":0}';

const CONTENT_TYPE = "application/json; charset=utf-8";

// Required by TanStack Start: tells the client the body is seroval-serialized.
const HEADERS = { "Content-Type": CONTENT_TYPE, "x-tss-serialized": "true" };

function handle(id, req, res) {
  const entry = captured[id];

  if (!entry) {
    res.writeHead(404, { "Content-Type": "text/plain" }).end("Unknown server function");
    return;
  }

  if (config.proxyServerFns) {
    proxy(req, res);
    return;
  }

  // POST-only endpoints (OAuth callbacks, sign in/out, token exchange).
  // The local demo has no real auth provider, so return a neutral success.
  if (entry.status === 405 || req.method === "POST") {
    res.writeHead(200, HEADERS).end(NULL_RESULT);
    return;
  }

  // Read endpoints: return the exact captured payload.
  res.writeHead(200, HEADERS).end(entry.body);
}

function proxy(req, res) {
  const headers = { ...req.headers };
  delete headers.host;
  delete headers["proxy-authorization"];
  delete headers["proxy-connection"];
  headers.origin = config.upstream;
  headers.referer = config.upstream + "/";

  const chunks = [];
  req.on("data", (c) => chunks.push(c));
  req.on("end", () => {
    const body = Buffer.concat(chunks);
    if (body.length) headers["content-length"] = body.length;
    const up = https.request(config.upstream + req.url, { method: req.method, headers }, (r) => {
      const h = { ...r.headers };
      delete h["content-length"];
      res.writeHead(r.statusCode || 502, h);
      r.pipe(res);
    });
    up.on("error", (e) =>
      res.writeHead(502, { "Content-Type": "text/plain" }).end("Proxy error: " + e.message)
    );
    up.end(body);
  });
}

module.exports = { handle, ids: Object.keys(captured), NULL_RESULT };
