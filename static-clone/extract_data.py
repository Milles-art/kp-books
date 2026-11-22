#!/usr/bin/env python3
"""Extract the real catalog/author data from the built JS into the admin JSON store."""
import json
import os
import re

HERE = os.path.dirname(os.path.abspath(__file__))
ASSETS = os.path.join(HERE, "site", "assets")
DATA_DIR = os.path.join(HERE, "backend", "data")
os.makedirs(DATA_DIR, exist_ok=True)


def txt(field, block):
    m = re.search(field + r':`([^`]*)`', block)
    if not m:
        m = re.search(field + r':"([^"]*)"', block)
    return m.group(1) if m else ""


def num(value):
    if value is None:
        return 0
    value = value.strip()
    m = re.match(r'^([\d.]+)e(\d+)$', value)
    if m:
        return int(float(m.group(1)) * 10 ** int(m.group(2)))
    try:
        return int(float(value))
    except ValueError:
        return 0


def boolval(s, field):
    m = re.search(field + r':(![01]|true|false)', s)
    if not m:
        return False
    return m.group(1) in ("!0", "true")


# ---------- Books ----------
cat = open(os.path.join(ASSETS, "catalog-BLS2xBf6.js"), encoding="utf-8").read()
start = cat.find("t=[{slug:")
end = cat.find("}];function", start)
blocks = cat[start + 2:end + 1].split("{slug:")
books = []
for b in blocks:
    if not b.strip().startswith("`"):
        continue
    b = "{slug:" + b
    access = re.search(r'access:\{([^}]*)\}', b)
    acc = access.group(1) if access else ""
    sample_block = re.search(r'sample:\{title:`([^`]*)`', b)
    books.append({
        "slug": txt("slug", b),
        "title": txt("title", b),
        "subtitle": txt("subtitle", b),
        "authorSlug": txt("authorSlug", b),
        "cover": txt("cover", b),
        "blurb": txt("blurb", b),
        "genre": txt("genre", b),
        "language": txt("language", b),
        "pageCount": num((re.search(r'pageCount:(\d+)', b) or [None, "0"])[1]),
        "publishedYear": num((re.search(r'publishedYear:(\d+)', b) or [None, "0"])[1]),
        "featured": boolval(b, "featured"),
        "isNew": boolval(b, "isNew"),
        "access": {
            "priceTzs": num((re.search(r'priceTzs:([\de.]+)', acc) or [None, "0"])[1]),
            "minPaymentTzs": num((re.search(r'minPaymentTzs:([\de.]+)', acc) or [None, "0"])[1]),
            "samplePages": num((re.search(r'samplePages:(\d+)', acc) or [None, "0"])[1]),
            "blockSize": num((re.search(r'blockSize:(\d+)', acc) or [None, "0"])[1]),
        },
        "sampleTitle": sample_block.group(1) if sample_block else "",
        "status": "published",
    })

# ---------- Authors ----------
au = open(os.path.join(ASSETS, "authors-B6u7lCWt.js"), encoding="utf-8").read()
ablocks = au[au.find("e=[{slug:") + 2:au.find("];function")].split("{slug:")
authors = []
for b in ablocks:
    if not b.strip().startswith("`"):
        continue
    b = "{slug:" + b
    bio = re.findall(r'`([^`]*)`', b.split("bio:[", 1)[1]) if "bio:[" in b else []
    authors.append({
        "slug": txt("slug", b),
        "name": txt("name", b),
        "role": txt("role", b),
        "portrait": txt("portrait", b),
        "location": txt("location", b),
        "bio": bio,
    })

store = {
    "books": books,
    "authors": authors,
    "orders": [],
    "users": [
        {"id": "usr_demo_1", "name": "Demo Reader", "email": "reader@example.com", "joined": "2026-01-14", "status": "active", "orders": 0, "spentTzs": 0},
        {"id": "usr_demo_2", "name": "Amina Yusuf", "email": "amina@example.com", "joined": "2026-02-03", "status": "active", "orders": 0, "spentTzs": 0},
    ],
    "settings": {
        "siteName": "KP Book",
        "tagline": "Read the culture. Live the story.",
        "contactEmail": "hello@kpwear.co.tz",
        "contactPhone": "0757 829 130",
        "location": "Dar es Salaam, Tanzania",
        "currency": "TSh",
        "minPaymentTzs": 1000,
        "paymentsEnabled": True,
    },
    "activity": [],
}

path = os.path.join(DATA_DIR, "admin.json")
json.dump(store, open(path, "w", encoding="utf-8"), indent=2, ensure_ascii=False)
print("wrote", path)
print("books:", len(books), "authors:", len(authors))
for b in books:
    print(" ", b["title"], b["genre"], b["access"]["priceTzs"], b["pageCount"])
