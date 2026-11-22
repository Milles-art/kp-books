#!/usr/bin/env node
// Admin REST API — backed by backend/data/admin.json, with a simple cookie session.
const fs = require("fs");
const path = require("path");
const crypto = require("crypto");

const FILE = path.join(__dirname, "data", "admin.json");
const USER = process.env.ADMIN_USER || "admin";
const PASS = process.env.ADMIN_PASS || "admin123";
const sessions = new Map();

function load() {
  return JSON.parse(fs.readFileSync(FILE, "utf8"));
}
function save(store) {
  fs.writeFileSync(FILE, JSON.stringify(store, null, 2) + "\n");
}
function json(res, code, data) {
  const body = JSON.stringify(data);
  res.writeHead(code, {
    "Content-Type": "application/json; charset=utf-8",
    "Content-Length": Buffer.byteLength(body),
    "Cache-Control": "no-store",
  });
  res.end(body);
}
function body(req) {
  return new Promise((resolve) => {
    const chunks = [];
    req.on("data", (c) => chunks.push(c));
    req.on("end", () => {
      try {
        resolve(JSON.parse(Buffer.concat(chunks).toString() || "{}"));
      } catch {
        resolve({});
      }
    });
  });
}
function cookie(req, name) {
  const raw = req.headers.cookie || "";
  for (const part of raw.split(";")) {
    const [k, ...v] = part.trim().split("=");
    if (k === name) return v.join("=");
  }
  return null;
}
function authed(req) {
  const token = cookie(req, "kpa_session");
  return !!(token && sessions.has(token));
}
function log(store, action, detail) {
  store.activity.unshift({
    id: crypto.randomUUID(),
    at: new Date().toISOString(),
    action,
    detail,
    by: USER,
  });
  store.activity = store.activity.slice(0, 100);
}

async function handle(req, res, url) {
  const p = url.pathname;
  const method = req.method;
  const store = load();

  // ---- Session (public) ----
  if (p === "/api/admin/session" && method === "GET") {
    return json(res, 200, {
      authenticated: authed(req),
      user: authed(req) ? { username: USER, role: "Administrator" } : null,
    });
  }
  if (p === "/api/admin/login" && method === "POST") {
    const b = await body(req);
    if (b.username === USER && b.password === PASS) {
      const token = crypto.randomBytes(24).toString("hex");
      sessions.set(token, Date.now());
      log(store, "login", "Administrator signed in");
      save(store);
      res.setHeader(
        "Set-Cookie",
        `kpa_session=${token}; HttpOnly; SameSite=Lax; Path=/; Max-Age=86400`
      );
      return json(res, 200, { ok: true });
    }
    return json(res, 401, { ok: false, error: "Invalid username or password" });
  }
  if (p === "/api/admin/logout" && method === "POST") {
    const token = cookie(req, "kpa_session");
    if (token) sessions.delete(token);
    res.setHeader("Set-Cookie", "kpa_session=; HttpOnly; SameSite=Lax; Path=/; Max-Age=0");
    return json(res, 200, { ok: true });
  }

  // Everything below requires a session.
  if (!authed(req)) return json(res, 401, { error: "Unauthorized" });

  if (p === "/api/admin/state" && method === "GET") return json(res, 200, store);

  // ---- Books ----
  if (p === "/api/admin/books" && method === "POST") {
    const b = await body(req);
    if (!b.slug || !b.title) return json(res, 400, { error: "slug and title are required" });
    if (store.books.some((x) => x.slug === b.slug))
      return json(res, 409, { error: "A book with that slug already exists" });
    store.books.push({ status: "published", access: {}, ...b });
    log(store, "book.create", `Added book "${b.title}"`);
    save(store);
    return json(res, 201, { ok: true });
  }
  if (p.startsWith("/api/admin/books/")) {
    const slug = decodeURIComponent(p.split("/")[4]);
    const i = store.books.findIndex((x) => x.slug === slug);
    if (i < 0) return json(res, 404, { error: "Book not found" });
    if (method === "PUT") {
      const b = await body(req);
      store.books[i] = {
        ...store.books[i],
        ...b,
        slug,
        access: { ...store.books[i].access, ...(b.access || {}) },
      };
      log(store, "book.update", `Updated "${store.books[i].title}"`);
      save(store);
      return json(res, 200, { ok: true, book: store.books[i] });
    }
    if (method === "DELETE") {
      const [removed] = store.books.splice(i, 1);
      log(store, "book.delete", `Removed "${removed.title}"`);
      save(store);
      return json(res, 200, { ok: true });
    }
  }

  // ---- Authors ----
  if (p === "/api/admin/authors" && method === "POST") {
    const b = await body(req);
    if (!b.slug || !b.name) return json(res, 400, { error: "slug and name are required" });
    if (store.authors.some((x) => x.slug === b.slug))
      return json(res, 409, { error: "An author with that slug already exists" });
    store.authors.push({ bio: [], ...b });
    log(store, "author.create", `Added author "${b.name}"`);
    save(store);
    return json(res, 201, { ok: true });
  }
  if (p.startsWith("/api/admin/authors/")) {
    const slug = decodeURIComponent(p.split("/")[4]);
    const i = store.authors.findIndex((x) => x.slug === slug);
    if (i < 0) return json(res, 404, { error: "Author not found" });
    if (method === "PUT") {
      const b = await body(req);
      store.authors[i] = { ...store.authors[i], ...b, slug };
      log(store, "author.update", `Updated "${store.authors[i].name}"`);
      save(store);
      return json(res, 200, { ok: true });
    }
    if (method === "DELETE") {
      const [removed] = store.authors.splice(i, 1);
      log(store, "author.delete", `Removed "${removed.name}"`);
      save(store);
      return json(res, 200, { ok: true });
    }
  }

  // ---- Orders ----
  if (p === "/api/admin/orders" && method === "POST") {
    const b = await body(req);
    const order = {
      id: crypto.randomUUID(),
      reference: b.reference || "KP" + Date.now().toString(36).toUpperCase(),
      bookSlug: b.bookSlug,
      bookTitle: store.books.find((x) => x.slug === b.bookSlug)?.title || b.bookTitle || "",
      amountTzs: Number(b.amountTzs) || 0,
      method: b.method || "mpesa",
      status: b.status || "completed",
      paidAt: b.paidAt || new Date().toISOString(),
      customer: b.customer || "Demo Reader",
    };
    store.orders.unshift(order);
    log(store, "order.create", `Order ${order.reference} (${order.amountTzs} TSh)`);
    save(store);
    return json(res, 201, { ok: true, order });
  }
  if (p.startsWith("/api/admin/orders/")) {
    const id = decodeURIComponent(p.split("/")[4]);
    const i = store.orders.findIndex((x) => x.id === id);
    if (i < 0) return json(res, 404, { error: "Order not found" });
    if (method === "PUT") {
      const b = await body(req);
      store.orders[i] = { ...store.orders[i], ...b, id };
      log(store, "order.update", `Order ${store.orders[i].reference} → ${store.orders[i].status}`);
      save(store);
      return json(res, 200, { ok: true });
    }
    if (method === "DELETE") {
      const [removed] = store.orders.splice(i, 1);
      log(store, "order.delete", `Deleted order ${removed.reference}`);
      save(store);
      return json(res, 200, { ok: true });
    }
  }

  // ---- Users ----
  if (p === "/api/admin/users" && method === "POST") {
    const b = await body(req);
    if (!b.email) return json(res, 400, { error: "email is required" });
    store.users.push({
      id: crypto.randomUUID(),
      name: b.name || b.email.split("@")[0],
      email: b.email,
      joined: new Date().toISOString().slice(0, 10),
      status: "active",
      orders: 0,
      spentTzs: 0,
    });
    log(store, "user.create", `Added user ${b.email}`);
    save(store);
    return json(res, 201, { ok: true });
  }
  if (p.startsWith("/api/admin/users/")) {
    const id = decodeURIComponent(p.split("/")[4]);
    const i = store.users.findIndex((x) => x.id === id);
    if (i < 0) return json(res, 404, { error: "User not found" });
    if (method === "PUT") {
      const b = await body(req);
      store.users[i] = { ...store.users[i], ...b, id };
      log(store, "user.update", `Updated user ${store.users[i].email}`);
      save(store);
      return json(res, 200, { ok: true });
    }
    if (method === "DELETE") {
      const [removed] = store.users.splice(i, 1);
      log(store, "user.delete", `Removed user ${removed.email}`);
      save(store);
      return json(res, 200, { ok: true });
    }
  }

  // ---- Settings ----
  if (p === "/api/admin/settings" && method === "PUT") {
    const b = await body(req);
    store.settings = { ...store.settings, ...b };
    log(store, "settings.update", "Site settings updated");
    save(store);
    return json(res, 200, { ok: true });
  }

  return json(res, 404, { error: "Unknown admin endpoint" });
}

module.exports = { handle, USER, PASS };
