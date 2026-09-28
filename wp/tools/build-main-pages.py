#!/usr/bin/env python3
"""
Build the three remaining main-site pages for WordPress: the services hub,
how-it-works and pricing.

Same contract as build-wp-content.py: the static .html files at the repo root
stay the source of truth and no copy is rewritten. What changes is the framing.

  * page bodies -> wp/plugin/brandsure-services/content/<slug>.blocks.html
                   (Gutenberg core/html blocks: the page stylesheet, then one
                   block per <section>, then the footer, then the WhatsApp pill)
  * SEO + JSON-LD -> wp/plugin/brandsure-services/content/main-pages.json

Two deliberate differences from the static file:

  * the per-page <header class="nav"> is dropped. Navigation comes from the one
    global header (includes/class-header.php), so no page carries its own copy.
  * the inline <script> is dropped. It is replaced by the plugin-enqueued
    assets/brandsure-mainsite.js, which is the same behaviour minus the nav
    handling the global header now owns. A real .js file also cannot be damaged
    by entity-escaping the way script markup pasted into a page can.

Run:  python3 wp/tools/build-main-pages.py
"""
import json
import pathlib
import re
from collections import OrderedDict

ROOT = pathlib.Path(__file__).resolve().parents[2]
PLUGIN = ROOT / "wp" / "plugin" / "brandsure-services"
CONTENT = PLUGIN / "content"

# static file stem -> WordPress slug.
#
# index.html becomes "start" rather than "home": page 42, the homepage that was
# live before this work, already holds the slug "home". Keeping it means the
# front page can be switched between the two with one setting instead of
# overwriting 60 KB of content that has no copy anywhere. A page serving as the
# front page is reached at "/" and WordPress redirects its own slug there, so
# the slug is never seen.
PAGES = OrderedDict([
    ("index", "start"),
    ("services", "services"),
    ("how-it-works", "how-it-works"),
    ("pricing", "pricing"),
])

SERVICE_SLUGS = [
    "furniture-growth-marketing-india",
    "furniture-business-consulting-india",
    "furniture-product-design-services-india",
    "furniture-labour-hiring-india",
]

LINK_MAP = OrderedDict([
    ("index.html", "/"),
    ("services.html", "/services/"),
    ("how-it-works.html", "/how-it-works/"),
    ("pricing.html", "/pricing/"),
    ("about.html", "/about/"),
    ("blog.html", "/blog/"),
    ("privacy-policy.html", "/privacy-policy/"),
    ("terms.html", "/terms-and-conditions/"),
])
for _s in SERVICE_SLUGS:
    LINK_MAP[_s + ".html"] = "/{}/".format(_s)

LIVE_HOST = "https://brandsure.in"
STATIC_HOSTS = ("https://www.brandsure.in", "http://www.brandsure.in")


def rewrite_links(html):
    for old, new in LINK_MAP.items():
        html = html.replace('href="{}"'.format(old), 'href="{}"'.format(new))
        html = html.replace('href="{}#'.format(old), 'href="{}#'.format(new))
    return html


def normalise_host(text):
    for h in STATIC_HOSTS:
        text = text.replace(h, LIVE_HOST)
    return text


def extract_style(html):
    """The page stylesheet: the first <style> block only.

    The second block is the <noscript> reveal fallback, which has no meaning
    inside page content -- the reveal safety net is a real script now.
    """
    return re.findall(r"<style>\n(.*?)\n</style>", html, re.S)[0]


def extract_sections(html):
    main = re.search(r"<main>\n(.*?)\n</main>", html, re.S).group(1)
    return [s.strip() for s in re.findall(r"^<section\b.*?^</section>$", main, re.S | re.M)]


def extract_footer(html):
    return re.search(r"^<footer.*?^</footer>$", html, re.S | re.M).group(0)


def extract_wapill(html):
    return re.search(r'^<a class="wapill".*?</a>$', html, re.S | re.M).group(0)


def extract_head_meta(html):
    head = html.split("</head>")[0]

    def meta(pattern):
        m = re.search(pattern, head)
        return m.group(1) if m else None

    return {
        "title": meta(r"<title>(.*?)</title>"),
        "description": meta(r'<meta name="description" content="(.*?)">'),
        "robots": meta(r'<meta name="robots" content="(.*?)">'),
        "canonical": meta(r'<link rel="canonical" href="(.*?)">'),
        "og_title": meta(r'<meta property="og:title" content="(.*?)">'),
        "og_description": meta(r'<meta property="og:description" content="(.*?)">'),
        "og_url": meta(r'<meta property="og:url" content="(.*?)">'),
        "og_image": meta(r'<meta property="og:image" content="(.*?)">'),
        "twitter_title": meta(r'<meta name="twitter:title" content="(.*?)">'),
        "twitter_description": meta(r'<meta name="twitter:description" content="(.*?)">'),
    }


def extract_jsonld(html):
    blocks = re.findall(
        r'<script type="application/ld\+json">(.*?)</script>', html, re.S
    )
    return [json.loads(normalise_host(b)) for b in blocks]


def h1_text(section):
    m = re.search(r'<h1 class="h1">(.*?)</h1>', section, re.S)
    return re.sub(r"<[^>]+>", "", m.group(1)).strip() if m else None


def blocks(style, sections, footer, wapill):
    parts = ["<style>\n" + style + "\n</style>"]
    parts.extend(sections)
    parts.append(footer)
    parts.append(wapill)
    return "\n\n".join(
        "<!-- wp:html -->\n" + p + "\n<!-- /wp:html -->" for p in parts
    ) + "\n"


BANNED = (
    "20%", "20 %", "twenty percent", "20 percent",
    "commission", "our cut", "revenue share", "share of sales",
    "Virtual Growth Partner", "fee from every sale",
)


def check_banned(slug, text):
    """Rule 1 and Rule 2 of the public-copy audit, enforced at build time."""
    lowered = text.lower()
    for term in BANNED:
        # 20% also appears in CSS transforms and %20 URL encoding, so only
        # flag it where it reads as prose.
        if term in ("20%", "20 %"):
            for m in re.finditer(re.escape(term), text):
                window = text[max(0, m.start() - 40):m.start()]
                if "%" == text[m.start() - 1:m.start()]:
                    continue  # %20 escape
                if re.search(r"(translate|scale|gradient|rotate|width|top|left|right|bottom|inset|opacity|blur)[^;{]*$", window, re.I):
                    continue
                raise SystemExit("{}: banned term {!r} in prose".format(slug, term))
            continue
        if term.lower() in lowered:
            raise SystemExit("{}: banned term {!r}".format(slug, term))


def main():
    CONTENT.mkdir(parents=True, exist_ok=True)
    out = OrderedDict()

    for stem, slug in PAGES.items():
        raw = (ROOT / (stem + ".html")).read_text(encoding="utf-8")
        html = normalise_host(rewrite_links(raw))

        sections = extract_sections(html)
        if len(sections) < 2:
            raise SystemExit("{}: only {} sections found".format(slug, len(sections)))
        h1s = sum(s.count("<h1") for s in sections)
        if h1s != 1:
            raise SystemExit("{}: expected exactly 1 <h1>, found {}".format(slug, h1s))

        body = blocks(
            extract_style(html),
            sections,
            extract_footer(html),
            extract_wapill(html),
        )
        if "<header class=\"nav\"" in body:
            raise SystemExit("{}: legacy header leaked into the page body".format(slug))
        if re.search(r"<script(?![^>]*ld\+json)", body):
            raise SystemExit("{}: inline script leaked into the page body".format(slug))
        check_banned(slug, body)

        (CONTENT / (slug + ".blocks.html")).write_text(body, encoding="utf-8")

        meta = extract_head_meta(html)
        meta["slug"] = slug
        meta["h1"] = h1_text(sections[0])
        meta["jsonld"] = extract_jsonld(html)
        meta["sections"] = len(sections)
        out[slug] = meta

    (CONTENT / "main-pages.json").write_text(
        json.dumps(out, indent=2, ensure_ascii=False) + "\n", encoding="utf-8"
    )

    for slug in PAGES.values():
        p = CONTENT / (slug + ".blocks.html")
        print("blocks:", slug, p.stat().st_size, "bytes,",
              out[slug]["sections"], "sections, h1:", out[slug]["h1"])


if __name__ == "__main__":
    main()
