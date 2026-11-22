#!/usr/bin/env python3
"""Extract Lucide icon path data from JS chunks and merge into the icon map."""
import glob
import json
import os
import re

HERE = os.path.dirname(os.path.abspath(__file__))
OUT = os.path.join(HERE, "backend", "data", "lucide-icons.json")
icons = json.load(open(OUT))


def balanced(s, start):
    depth = 0
    for i in range(start, len(s)):
        if s[i] == '[':
            depth += 1
        elif s[i] == ']':
            depth -= 1
            if depth == 0:
                return s[start:i + 1]
    return None


def js_to_json(s):
    # JS object literals here use backtick strings and unquoted keys only inside objects.
    s = s.replace('`', '"')
    s = re.sub(r'([{,])([A-Za-z_$][\w$]*):', r'\1"\2":', s)
    return s


for f in glob.glob(os.path.join(HERE, "site", "assets", "*.js")):
    s = open(f, encoding="utf-8", errors="replace").read()
    for m in re.finditer(r'name:`([a-z0-9-]+)`,size:\d+,node:', s):
        name = m.group(1)
        if name in icons:
            continue
        arr = balanced(s, m.end())
        if not arr:
            continue
        try:
            nodes = json.loads(js_to_json(arr))
        except Exception:
            continue
        parts = []
        for node in nodes:
            tag, attrs = node[0], node[1]
            a = " ".join(f'{k}="{v}"' for k, v in attrs.items() if k != "key")
            parts.append(f"<{tag} {a}></{tag}>")
        icons[name] = (
            '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" '
            'viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" '
            'stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-' + name + '" '
            'aria-hidden="true">' + "".join(parts) + "</svg>"
        )

json.dump(icons, open(OUT, "w"), indent=2)
print("total icons:", len(icons))
print("added:", ", ".join(sorted(set(icons) - {
    'arrow-left','arrow-right','arrow-up-right','book-open','bookmark','chevron-left',
    'chevron-right','coins','folder-plus','globe','layers','library-big','lock-open',
    'mail','map-pin','menu','moon','phone','search','shield-check','smartphone','sun'
})))
