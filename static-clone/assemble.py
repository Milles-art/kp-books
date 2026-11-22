#!/usr/bin/env python3
"""Assemble mirrored assets into a self-contained static site."""
import os
import shutil

HERE = os.path.dirname(os.path.abspath(__file__))
SITE = os.path.join(HERE, "site")
SSR = os.path.join(HERE, "_ssr")

# Route -> SSR file
ROUTES = {
    "/": "index",
    "/books": "books",
    "/authors": "authors",
    "/library": "library",
    "/wishlist": "wishlist",
    "/books/mtaa-wa-kazi": "books__mtaa-wa-kazi",
    "/books/the-salt-road": "books__the-salt-road",
    "/books/konfidence": "books__konfidence",
    "/books/build-better": "books__build-better",
    "/books/kiboko-na-mvua": "books__kiboko-na-mvua",
    "/books/kilimanjaro-mornings": "books__kilimanjaro-mornings",
    "/read/mtaa-wa-kazi": "read__mtaa-wa-kazi",
    "/read/the-salt-road": "read__the-salt-road",
    "/read/konfidence": "read__konfidence",
    "/read/build-better": "read__build-better",
    "/read/kiboko-na-mvua": "read__kiboko-na-mvua",
    "/read/kilimanjaro-mornings": "read__kilimanjaro-mornings",
    "/authors/masoud-kipanya": "authors__masoud-kipanya",
    "/authors/zawadi-mrisho": "authors__zawadi-mrisho",
    "/authors/juma-kassim": "authors__juma-kassim",
}

for route, name in ROUTES.items():
    dest_dir = os.path.join(SITE, route.strip("/"))
    os.makedirs(dest_dir, exist_ok=True)
    shutil.copyfile(os.path.join(SSR, name + ".html"), os.path.join(dest_dir, "index.html"))
    print("page", route, "->", os.path.join(dest_dir, "index.html"))

# Normalise the font CSS filename (strip the query string from mirror.py).
font_dir = os.path.join(SITE, "_hercules", "font")
for f in os.listdir(font_dir):
    if f.startswith("css2"):
        src = os.path.join(font_dir, f)
        dst = os.path.join(font_dir, "css2")
        shutil.move(src, dst)
        print("font css ->", dst)
        break

# Make CDN images local across all downloaded text files.
changed = 0
for root, _, files in os.walk(SITE):
    for f in files:
        path = os.path.join(root, f)
        if not f.endswith((".html", ".js", ".css")):
            continue
        try:
            text = open(path, encoding="utf-8").read()
        except UnicodeDecodeError:
            continue
        new = text.replace("https://hercules-cdn.com/", "/")
        if new != text:
            open(path, "w", encoding="utf-8").write(new)
            changed += 1
            print("localised images in", os.path.relpath(path, SITE))
print("files changed:", changed)
print("ASSEMBLED")
