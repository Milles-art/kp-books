#!/usr/bin/env python3
"""Verify every page and every referenced local asset exists on disk."""
import os
import re
import html as htmlmod

HERE = os.path.dirname(os.path.abspath(__file__))
SITE = os.path.join(HERE, "site")

ROUTES = [
    "/", "/books", "/authors", "/library", "/wishlist",
    "/books/mtaa-wa-kazi", "/books/the-salt-road", "/books/konfidence",
    "/books/build-better", "/books/kiboko-na-mvua", "/books/kilimanjaro-mornings",
    "/read/mtaa-wa-kazi", "/read/the-salt-road", "/read/konfidence",
    "/read/build-better", "/read/kiboko-na-mvua", "/read/kilimanjaro-mornings",
    "/authors/masoud-kipanya", "/authors/zawadi-mrisho", "/authors/juma-kassim",
]

print("=== PAGES ===")
missing_pages = []
for r in ROUTES:
    p = os.path.join(SITE, r.strip("/"), "index.html")
    ok = os.path.isfile(p)
    if not ok:
        missing_pages.append(r)
    print(("OK  " if ok else "MISS"), r, "->", os.path.relpath(p, SITE))
print("pages:", len(ROUTES) - len(missing_pages), "/", len(ROUTES))

print("\n=== LOCAL ASSETS REFERENCED BY HTML ===")
missing_assets = set()
checked = 0
for r in ROUTES:
    p = os.path.join(SITE, r.strip("/"), "index.html")
    text = open(p, encoding="utf-8", errors="replace").read()
    for u in re.findall(r'(?:href|src)="([^"]+)"', htmlmod.unescape(text)):
        if u.startswith("/") and not u.startswith("//"):
            path_only = u.split("?")[0]
            # Internal page links resolve to a directory with index.html.
            if path_only in ROUTES:
                page_index = os.path.join(SITE, path_only.strip("/"), "index.html")
                if not os.path.isfile(page_index):
                    missing_assets.add(u)
                continue
            checked += 1
            f = os.path.join(SITE, path_only.lstrip("/"))
            if not os.path.isfile(f):
                missing_assets.add(u)
print("references checked:", checked)
print("missing:", sorted(missing_assets) if missing_assets else "none")

print("\n=== FONT / CSS url() ASSETS ===")
missing_css = set()
for root, _, files in os.walk(SITE):
    for f in files:
        if not f.endswith((".css", ".js")):
            continue
        p = os.path.join(root, f)
        try:
            text = open(p, encoding="utf-8").read()
        except UnicodeDecodeError:
            continue
        for u in re.findall(r'url\(["\']?(/[^\)"\']+)', text):
            f2 = os.path.join(SITE, u.lstrip("/").split("?")[0])
            if not os.path.isfile(f2):
                missing_css.add(u)
print("missing:", sorted(missing_css) if missing_css else "none")

print("\n=== CDN URLS LEFT (should be none) ===")
leftover = set()
for root, _, files in os.walk(SITE):
    for f in files:
        if f.endswith((".html", ".js", ".css")):
            p = os.path.join(root, f)
            try:
                t = open(p, encoding="utf-8").read()
            except UnicodeDecodeError:
                continue
            if "hercules-cdn.com" in t:
                leftover.add(os.path.relpath(p, SITE))
print("files with CDN URLs:", sorted(leftover) if leftover else "none")

ok = not missing_pages and not missing_assets and not missing_css and not leftover
print("\nRESULT:", "ALL GOOD" if ok else "PROBLEMS FOUND")
