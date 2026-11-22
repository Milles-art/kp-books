#!/usr/bin/env python3
"""Align the admin UI with the storefront: Lucide SVGs + the site's design tokens."""
import json
import os

HERE = os.path.dirname(os.path.abspath(__file__))
HTML = os.path.join(HERE, "site", "admin", "index.html")
ICONS = os.path.join(HERE, "backend", "data", "lucide-icons.json")

s = open(HTML, encoding="utf-8").read()
icons = json.load(open(ICONS))

# Link the storefront's compiled stylesheet so the same utilities/tokens are available.
s = s.replace(
    '<link rel="stylesheet" href="/_hercules/font/css2" />',
    '<link rel="stylesheet" href="/_hercules/font/css2" />\n<link rel="stylesheet" href="/assets/index-DtSTgV8a.css" />',
)

# Replace the approximate palette with the storefront's exact dark-theme tokens.
old_root = s[s.index(":root{"):s.index("}\n*{box-sizing", s.index(":root{")) + 1]
new_root = """:root{
  --bg:oklch(17% .018 158); --panel:oklch(21.5% .022 158); --panel2:oklch(27% .03 158);
  --line:oklch(100% 0 0/.11); --text:oklch(96% .01 95); --muted:oklch(72% .02 120);
  --green:#0a3d27; --green2:#7fd1a6; --red:oklch(64% .21 26); --red2:oklch(68% .2 25);
  --amber:oklch(80% .12 85);
}"""
s = s.replace(old_root, new_root)

# Storefront primary is red; the deep green is the secondary/brand accent.
s = s.replace(".btn-primary{background:var(--green);color:#fff}", ".btn-primary{background:var(--red);color:#fff}")
s = s.replace(".btn-primary:hover{background:#0d5134;transform:translateY(-1px)}", ".btn-primary:hover{background:var(--red2);transform:translateY(-1px)}")
s = s.replace(".nav a.active{background:var(--green);color:#fff}", ".nav a.active{background:var(--green);color:#fff}")

# Inject the exact Lucide SVGs and a helper.
inject = "const LUCIDE = " + json.dumps(icons, ensure_ascii=False) + ";\n" + """
const icon = (name, size=18) => {
  const raw = LUCIDE[name] || '';
  return raw.replace('width="24" height="24"', `width="${size}" height="${size}"`);
};
const navIcon = {overview:'layers',books:'book-open',authors:'library-big',orders:'coins',users:'folder',settings:'ellipsis'};
"""
s = s.replace("const icons = {\n  overview:'▦', books:'▤', authors:'◉', orders:'◈', users:'♙', settings:'⚙'\n};", inject)

# Glyph -> Lucide replacements.
repls = [
    ("<span class=\"ic\">${icons[k]}</span>", "<span class=\"ic\">${icon(navIcon[k],17)}</span>"),
    ("↗ <span>View site</span>", "${icon('arrow-up-right',16)} <span>View site</span>"),
    ("⇥ <span>Sign out</span>", "${icon('arrow-left',16)} <span>Sign out</span>"),
    ("↗ OPEN STOREFRONT", "${icon('arrow-up-right',14)} OPEN STOREFRONT"),
    ("+ Add book", "${icon('plus',14)} Add book"),
    ("+ Add author", "${icon('plus',14)} Add author"),
    ("+ Add order", "${icon('plus',14)} Add order"),
    ("+ Add reader", "${icon('plus',14)} Add reader"),
    ("↻ Import demo payments", "${icon('folder-plus',14)} Import demo payments"),
    ("↻ Import from ledger", "${icon('folder-plus',14)} Import from ledger"),
    ('title="Edit">✎</button>', 'title="Edit">${icon(\'pencil\',13)}</button>'),
    ('title="Delete">×</button>', 'title="Delete">${icon(\'trash\',13)}</button>'),
    ('title="Toggle status">↻</button>', 'title="Toggle status">${icon(\'circle-check\',13)}</button>'),
    ('data-edit="${escapeHtml(a.slug)}">✎</button>', 'data-edit="${escapeHtml(a.slug)}">${icon(\'pencil\',13)}</button>'),
    ('data-del="${escapeHtml(a.slug)}">×</button>', 'data-del="${escapeHtml(a.slug)}">${icon(\'trash\',13)}</button>'),
    ('data-edit="${escapeHtml(u.id)}">✎</button>', 'data-edit="${escapeHtml(u.id)}">${icon(\'pencil\',13)}</button>'),
    ('data-del="${escapeHtml(u.id)}">×</button>', 'data-del="${escapeHtml(u.id)}">${icon(\'trash\',13)}</button>'),
    ('data-del="${escapeHtml(o.id)}">×</button>', 'data-del="${escapeHtml(o.id)}">${icon(\'trash\',13)}</button>'),
]
for a, b in repls:
    s = s.replace(a, b)

open(HTML, "w", encoding="utf-8").write(s)
print("patched:", HTML)
print("remaining glyphs:", [g for g in ['▦','▤','◉','◈','♙','⚙','✎','↻','⇥'] if g in s])
