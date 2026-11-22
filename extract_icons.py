#!/usr/bin/env python3
"""Extract every Lucide SVG from the captured storefront HTML into a JSON map."""
import glob
import json
import os
import re

HERE = os.path.dirname(os.path.abspath(__file__))
files = glob.glob(os.path.join(HERE, "_ssr", "*.html"))
icons = {}
for f in files:
    s = open(f, encoding="utf-8", errors="replace").read()
    for m in re.finditer(r'<svg[^>]*class="[^"]*lucide lucide-([a-z0-9-]+)[^"]*"[^>]*>.*?</svg>', s, re.S):
        name = m.group(1)
        if name not in icons:
            icons[name] = m.group(0)
print("extracted", len(icons), "icons:", ", ".join(sorted(icons)))
out = os.path.join(HERE, "backend", "data", "lucide-icons.json")
json.dump(icons, open(out, "w"), indent=2)
print("saved", out)
