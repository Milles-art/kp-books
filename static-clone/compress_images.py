#!/usr/bin/env python3
"""Convert the large PNG cover/author images to WebP and rewrite all references."""
import os
from PIL import Image

HERE = os.path.dirname(os.path.abspath(__file__))
SITE = os.path.join(HERE, "site")

names = [f for f in os.listdir(SITE) if f.startswith("file_") and "." not in f]
total_before = total_after = 0

for name in names:
    src = os.path.join(SITE, name)
    dst = os.path.join(SITE, name + ".webp")
    before = os.path.getsize(src)
    with Image.open(src) as im:
        im.save(dst, "WEBP", quality=82, method=6)
    after = os.path.getsize(dst)
    total_before += before
    total_after += after
    print(f"{name}: {before/1e6:.2f} MB -> {after/1e6:.2f} MB ({100*after/before:.1f}%)")

# Rewrite references in every text asset (HTML/JS/CSS).
changed = []
for root, _, files in os.walk(SITE):
    for f in files:
        if not f.endswith((".html", ".js", ".css")):
            continue
        p = os.path.join(root, f)
        try:
            text = open(p, encoding="utf-8").read()
        except UnicodeDecodeError:
            continue
        new = text
        for name in names:
            new = new.replace("/" + name + "?",
                              "/" + name + ".webp?").replace("/" + name + '"',
                              "/" + name + '.webp"').replace("/" + name + '`',
                              "/" + name + '.webp`')
        # Also handle bare occurrences followed by non-identifier chars generically.
        if new != text:
            open(p, "w", encoding="utf-8").write(new)
            changed.append(os.path.relpath(p, SITE))

# Remove the originals.
for name in names:
    os.remove(os.path.join(SITE, name))

print(f"\nImages: {total_before/1e6:.1f} MB -> {total_after/1e6:.1f} MB")
print("Files with rewritten refs:", len(changed))
