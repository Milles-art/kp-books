#!/usr/bin/env python3
"""Make the admin theme-aware and align remaining tokens with the storefront."""
import os

HERE = os.path.dirname(os.path.abspath(__file__))
HTML = os.path.join(HERE, "site", "admin", "index.html")
s = open(HTML, encoding="utf-8").read()

s = s.replace('<html lang="en" class="dark">', '<html lang="en">')

old = s[s.index(":root{"):s.index("}\n*{box-sizing", s.index(":root{")) + 1]
new = """:root{
  /* Light theme — exact storefront tokens */
  --bg:oklch(98.2% .008 95); --panel:oklch(100% 0 0); --panel2:oklch(95% .01 100);
  --line:oklch(89% .012 110); --rowline:oklch(89% .012 110 / .6);
  --text:oklch(20% .012 150); --muted:oklch(47% .02 150);
  --red:oklch(59.4% .217 26); --red2:oklch(55% .22 27);
  --green:#0a3d27; --green2:#12663f; --amber:oklch(78% .12 85);
  --input:oklch(100% 0 0); --hover:rgba(0,0,0,.05);
  --pillbg:rgba(18,102,63,.12);
}
:root.dark{
  /* Dark theme — exact storefront tokens */
  --bg:oklch(17% .018 158); --panel:oklch(21.5% .022 158); --panel2:oklch(27% .03 158);
  --line:oklch(100% 0 0/.11); --rowline:rgba(255,255,255,.05);
  --text:oklch(96% .01 95); --muted:oklch(72% .02 120);
  --red:oklch(64% .21 26); --red2:oklch(68% .2 25);
  --green2:#7fd1a6; --amber:oklch(80% .12 85);
  --input:#0a1410; --hover:rgba(255,255,255,.05);
  --pillbg:rgba(127,209,166,.14);
}"""
s = s.replace(old, new)

repls = [
    ("input,select,textarea{width:100%;background:#0a1410;", "input,select,textarea{width:100%;background:var(--input);"),
    (".btn{border:0;border-radius:10px;", ".btn{border:0;border-radius:9999px;"),
    (".nav a{display:flex;align-items:center;gap:12px;padding:11px 13px;border-radius:10px;color:var(--muted);font-weight:600;cursor:pointer;transition:.15s}",
     ".nav a{display:flex;align-items:center;gap:12px;padding:11px 13px;border-radius:9999px;color:var(--muted);font-weight:600;font-size:14px;text-transform:uppercase;letter-spacing:.05em;cursor:pointer;transition:.15s}"),
    (".nav a.active{background:var(--green);color:#fff}",
     ".nav a.active{background:oklch(27% .03 158);color:var(--text)}"),
    (".side-foot a:hover,.side-foot button:hover{background:rgba(255,255,255,.05);color:var(--text)}",
     ".side-foot a:hover,.side-foot button:hover{background:var(--hover);color:var(--text)}"),
    ("td{padding:12px 15px;border-bottom:1px solid rgba(255,255,255,.05);vertical-align:middle}",
     "td{padding:12px 15px;border-bottom:1px solid var(--rowline);vertical-align:middle}"),
    (".pill.green{background:rgba(127,209,166,.14);color:var(--green2)}",
     ".pill.green{background:var(--pillbg);color:var(--green2)}"),
    (".act{display:flex;gap:12px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.05)}",
     ".act{display:flex;gap:12px;padding:10px 0;border-bottom:1px solid var(--rowline)}"),
    ('<button id="logout">${icon(\'arrow-left\',16)} <span>Sign out</span></button>',
     '<button id="toggle-theme">${icon(\'sun\',16)} <span>Theme</span></button>\n        <button id="logout">${icon(\'arrow-left\',16)} <span>Sign out</span></button>'),
]
for a, b in repls:
    if a not in s:
        print("MISS:", a[:70])
    s = s.replace(a, b)

# Theme boot + toggle, using the same localStorage key as the storefront.
s = s.replace("const $ = (s, r=document) => r.querySelector(s);", """(function(){
  let t = localStorage.getItem('theme');
  if(t!=='light'&&t!=='dark'&&t!=='system') t='system';
  const d = matchMedia('(prefers-color-scheme: dark)').matches;
  document.documentElement.classList.toggle('dark', t==='system' ? d : t==='dark');
})();
const $ = (s, r=document) => r.querySelector(s);""")

s = s.replace("  $('#logout').onclick = async ()=>", """  $('#toggle-theme').onclick = ()=>{
    const dark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('theme', dark?'dark':'light');
    renderShell();
  };
  $('#logout').onclick = async ()=>""")

open(HTML, "w", encoding="utf-8").write(s)
print("patched")
