#!/usr/bin/env python3
"""Mirror the KP Book site: all routes' SSR HTML + every static asset.

Run from the kp-book directory: python3 mirror.py
"""
import html
import os
import re
import subprocess
from urllib.parse import urlparse, parse_qs

BASE = "https://kipanya-digital-books-552698.onhercules.app"
SITE = os.path.dirname(os.path.abspath(__file__))

ROUTES = [
    "/",
    "/books",
    "/authors",
    "/library",
    "/wishlist",
    "/books/mtaa-wa-kazi",
    "/books/the-salt-road",
    "/books/konfidence",
    "/books/build-better",
    "/books/kiboko-na-mvua",
    "/books/kilimanjaro-mornings",
    "/read/mtaa-wa-kazi",
    "/read/the-salt-road",
    "/read/konfidence",
    "/read/build-better",
    "/read/kiboko-na-mvua",
    "/read/kilimanjaro-mornings",
    "/authors/masoud-kipanya",
    "/authors/zawadi-mrisho",
    "/authors/juma-kassim",
]

ASSET_DIR = os.path.join(SITE, "site")
SSR_DIR = os.path.join(SITE, "_ssr")


def fetch(url, dest=None, raw=False):
    """Download url via curl to dest path. Returns bytes."""
    cmd = ["curl", "-sS", "-L", "--compressed", "-m", "60", url]
    if dest is not None:
        os.makedirs(os.path.dirname(dest), exist_ok=True)
        cmd += ["-o", dest]
    out = subprocess.run(cmd, capture_output=True)
    if out.returncode != 0:
        print("  FAIL", url, out.stderr.decode(errors="replace")[:200])
        return b""
    if dest is None:
        return out.stdout
    return b""


def local_path(url):
    """Map a URL to a local path inside site/, keeping query strings for fonts."""
    p = urlparse(url)
    path = p.path.lstrip("/")
    if not path:
        path = "index.html"
    if p.query:
        # Preserve query so font CSS with ?family=... is distinguishable.
        path += "__QUERY__" + p.query
    return os.path.join(ASSET_DIR, path)


# 1. Fetch each route's SSR HTML (for reference / fallback) and collect asset URLs.
asset_urls = set()
html_by_route = {}
for route in ROUTES:
    name = "index" if route == "/" else route.strip("/").replace("/", "__")
    dest = os.path.join(SSR_DIR, name + ".html")
    fetch(BASE + route, dest)
    text = open(dest, encoding="utf-8", errors="replace").read()
    html_by_route[route] = text
    unescaped = html.unescape(text)
    for m in re.findall(r'(?:href|src)="([^"]+)"', unescaped):
        if m.startswith("/assets/") or m.startswith("/_hercules/") or m == "/favicon.ico" \
           or m.startswith("https://hercules-cdn.com/"):
            asset_urls.add(m)
    # bare quoted asset paths in inline JS manifests
    for m in re.findall(r'"(/assets/[^"]+)"', unescaped):
        asset_urls.add(m)
    print("html", route, len(text))

# 2. Download all discovered assets.
print("assets discovered:", len(asset_urls))
for url in sorted(asset_urls):
    full = url if url.startswith("http") else BASE + url
    dest = local_path(url)
    fetch(full, dest)
    print("  asset", url)

# 3. Parse the Google-fonts-style CSS and download referenced woff2 files.
font_css_paths = [p for p in asset_urls if p.startswith("/_hercules/font/css2")]
for css_url in font_css_paths:
    p = local_path(css_url)
    css_path = p + ""  # file already downloaded by step 2
    if not os.path.exists(css_path):
        continue
    css = open(css_path, encoding="utf-8", errors="replace").read()
    for m in re.findall(r'url\(([^)]+)\)', css):
        u = m.strip().strip("'\"")
        if u.startswith("/"):
            fetch(BASE + u, local_path(u))
        elif u.startswith("http"):
            fetch(u, local_path(u))
        print("  font", u)

# 4. Parse all local CSS for any additional url() assets.
for root, _, files in os.walk(os.path.join(ASSET_DIR, "assets")):
    for f in files:
        if not f.endswith(".css"):
            continue
        fp = os.path.join(root, f)
        css = open(fp, encoding="utf-8", errors="replace").read()
        for m in re.findall(r'url\(([^)]+)\)', css):
            u = m.strip().strip("'\"")
            if u.startswith("/") and not u.startswith("//"):
                fetch(BASE + u, local_path(u))
                print("  css asset", u)

print("DONE")
