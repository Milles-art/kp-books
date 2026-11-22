#!/usr/bin/env python3
"""Capture the response of every TanStack server function used by the frontend."""
import os
import re
import subprocess
import json

HERE = os.path.dirname(os.path.abspath(__file__))
JS = os.path.join(HERE, "site", "assets", "index-BVLawKKe.js")
BASE = "https://kipanya-digital-books-552698.onhercules.app"

s = open(JS, encoding="utf-8").read()
ids = sorted(set(re.findall(r"va\(`?([0-9a-f]{40,})`?\)", s)))
print("found", len(ids), "server functions")

out = {}
for fid in ids:
    url = BASE + "/_serverFn/" + fid
    cmd = [
        "curl", "-sS", "-m", "30",
        "-H", "x-tsr-serverfn: true",
        "-H", "accept: application/x-tss-framed, application/x-ndjson, application/json",
        "-H", "origin: " + BASE,
        "-H", "referer: " + BASE + "/",
        "-w", "\n__HTTP__%{http_code}",
        url,
    ]
    r = subprocess.run(cmd, capture_output=True)
    raw = r.stdout.decode(errors="replace")
    if "__HTTP__" in raw:
        body, code = raw.rsplit("\n__HTTP__", 1)
        code = int(code.strip())
    else:
        body, code = raw, 0
    out[fid] = {"status": code, "body": body[:4000]}
    print(fid[:12], "->", code, len(body), "bytes", repr(body[:90]))

path = os.path.join(HERE, "server-fns-captured.json")
json.dump(out, open(path, "w"), indent=2)
print("saved", path)
