#!/usr/bin/env python3
"""
Build WordPress artefacts from the existing static BrandSure service pages.

Source of truth = the static .html files at the repo root. This script does not
rewrite copy; it only re-frames the existing markup for WordPress:

  * shared CSS  -> wp/plugin/brandsure-services/assets/brandsure-services.css
  * shared JS   -> wp/plugin/brandsure-services/assets/brandsure-services.js
  * page bodies -> wp/plugin/brandsure-services/content/<slug>.blocks.html
                   (Gutenberg core/html sections, one block per <section>)
  * SEO + JSON-LD -> wp/plugin/brandsure-services/content/seo.json

Run:  python3 wp/tools/build-wp-content.py
"""
import json
import pathlib
import re
from collections import OrderedDict

ROOT = pathlib.Path(__file__).resolve().parents[2]
PLUGIN = ROOT / "wp" / "plugin" / "brandsure-services"
ASSETS = PLUGIN / "assets"
CONTENT = PLUGIN / "content"

SLUGS = [
    "furniture-growth-marketing-india",
    "furniture-business-consulting-india",
    "furniture-product-design-services-india",
    "furniture-labour-hiring-india",
]

# Static filename -> WordPress permalink. Pages that do not exist yet in
# WordPress still get their canonical pretty permalink so nothing points at
# a .html file that WordPress will never serve.
LINK_MAP = OrderedDict([
    ("index.html", "/"),
    ("services.html", "/services/"),
    ("how-it-works.html", "/how-it-works/"),
    ("pricing.html", "/pricing/"),
    ("about.html", "/about/"),
    ("privacy-policy.html", "/privacy-policy/"),
    ("terms.html", "/terms-and-conditions/"),
])
for s in SLUGS:
    LINK_MAP[s + ".html"] = "/{}/".format(s)

# The live site serves the bare domain (WordPress.com primary_domain =
# brandsure.in). The static files were authored against www.brandsure.in, which
# would make every canonical point at a redirect, so normalise to the host that
# is actually served.
LIVE_HOST = "https://brandsure.in"
STATIC_HOSTS = ("https://www.brandsure.in", "http://www.brandsure.in")


def read(slug):
    return (ROOT / (slug + ".html")).read_text(encoding="utf-8")


def rewrite_links(html):
    """Point relative .html links at their WordPress permalinks."""
    for old, new in LINK_MAP.items():
        html = html.replace('href="{}"'.format(old), 'href="{}"'.format(new))
        html = html.replace('href="{}#'.format(old), 'href="{}#'.format(new))
    return html


def normalise_host(text):
    for h in STATIC_HOSTS:
        text = text.replace(h, LIVE_HOST)
    return text


def extract_style(html):
    return re.search(r"<style>\n(.*?)\n</style>", html, re.S).group(1)


def extract_main(html):
    return re.search(r"<main>\n(.*?)\n</main>", html, re.S).group(1)


def split_sections(main_html):
    """Split <main> into its top-level <section> elements, dropping inline
    <script> (those move into the shared JS bundle)."""
    sections = re.findall(r"^<section\b.*?^</section>$", main_html, re.S | re.M)
    return [s.strip() for s in sections]


def extract_wapill(html):
    m = re.search(r'^<a class="wapill".*?</a>$', html, re.S | re.M)
    return m.group(0) if m else None


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
    raw = re.search(
        r'<script type="application/ld\+json">(.*?)</script>', html, re.S
    ).group(1)
    return json.loads(normalise_host(raw))


def h1_text(sections):
    m = re.search(r'<h1 class="h1">(.*?)</h1>', sections[0], re.S)
    return m.group(1).strip() if m else None


SCOPE_ROOTS = (".hero", ".band", ".wapill")


def _expand(template):
    """Expand a selector template across every top-level section root.

    The static pages owned the whole document, so their resets targeted bare
    elements. Every top-level block on a service page is .hero, .band or the
    fixed .wapill, so the resets are re-pointed at those instead of at a wrapper
    element -- that keeps each section a self-contained, valid Custom HTML block.
    """
    return ",".join(template.format(root=r) for r in SCOPE_ROOTS)


def scope_css(css):
    """Confine the reset/element rules to the service-page sections.

    Custom properties stay on :root so they resolve everywhere.
    """
    replacements = [
        ("*,*::before,*::after{box-sizing:border-box}",
         _expand("{root},{root} *,{root} *::before,{root} *::after")
         + "{box-sizing:border-box}"),
        ("html{-webkit-text-size-adjust:100%;scroll-behavior:smooth;scroll-padding-top:76px}",
         "html{scroll-behavior:smooth;scroll-padding-top:76px}"),
        ("h1,h2,h3,h4{color:var(--ink);margin:0;font-weight:700;letter-spacing:-.03em;line-height:1.15}",
         _expand("{root} h1,{root} h2,{root} h3,{root} h4")
         + "{color:var(--ink);margin:0;font-weight:700;letter-spacing:-.03em;line-height:1.15}"),
        ("p{margin:0}a{color:inherit;text-decoration:none}button{font:inherit;cursor:pointer}",
         _expand("{root} p") + "{margin:0}"
         + _expand("{root} a") + "{color:inherit;text-decoration:none}"
         + _expand("{root} button") + "{font:inherit;cursor:pointer}"),
        ("img{max-width:100%;display:block;height:auto}",
         _expand("{root} img") + "{max-width:100%;display:block;height:auto}"),
        ("::selection{background:var(--green);color:var(--ink)}",
         _expand("{root} ::selection") + "{background:var(--green);color:var(--ink)}"),
        (":focus-visible{outline:2px solid var(--blue);outline-offset:3px;border-radius:4px}",
         _expand("{root} :focus-visible")
         + "{outline:2px solid var(--blue);outline-offset:3px;border-radius:4px}"),
    ]
    for old, new in replacements:
        if old not in css:
            raise SystemExit("CSS scoping failed, rule not found:\n  " + old)
        css = css.replace(old, new, 1)

    # body{...} carried the page typography; move those declarations onto the
    # section roots so the theme's header and footer keep their own type.
    body_rule = re.search(r"^body\{(.*?)\}$", css, re.S | re.M)
    if not body_rule:
        raise SystemExit("CSS scoping failed: body rule not found")
    decls = body_rule.group(1)
    decls = decls.replace("margin:0;", "").replace("overflow-x:hidden", "").strip().strip(";")
    css = css.replace(body_rule.group(0), _expand("{root}") + "{" + decls + "}", 1)
    return css


def gutenberg(sections, wapill):
    """One core/html block per <section> so each is separately editable.

    No wrapper element: the stylesheet targets the section classes directly, so
    every block is balanced markup that the editor accepts as-is.
    """
    blocks = ["<!-- wp:html -->\n" + sec + "\n<!-- /wp:html -->" for sec in sections]
    if wapill:
        blocks.append("<!-- wp:html -->\n" + wapill + "\n<!-- /wp:html -->")
    return "\n\n".join(blocks) + "\n"


def main():
    ASSETS.mkdir(parents=True, exist_ok=True)
    CONTENT.mkdir(parents=True, exist_ok=True)

    styles, seo, extra_js = {}, OrderedDict(), []

    for slug in SLUGS:
        html = normalise_host(rewrite_links(read(slug)))
        styles[slug] = extract_style(html)

        main_html = extract_main(html)
        sections = split_sections(main_html)
        # Section count varies per page and changes when copy is restructured;
        # what must hold is that the page splits into sections and has one H1.
        if len(sections) < 8:
            raise SystemExit("{}: only {} sections found".format(slug, len(sections)))
        h1s = sum(s.count("<h1") for s in sections)
        if h1s != 1:
            raise SystemExit("{}: expected exactly 1 <h1>, found {}".format(slug, h1s))

        for js in re.findall(r"^<script>\n(.*?)\n</script>$", main_html, re.S | re.M):
            extra_js.append((slug, js))

        (CONTENT / (slug + ".blocks.html")).write_text(
            gutenberg(sections, extract_wapill(html)), encoding="utf-8"
        )

        meta = extract_head_meta(html)
        meta["slug"] = slug
        meta["h1"] = h1_text(sections)
        meta["jsonld"] = extract_jsonld(html)
        seo[slug] = meta

    # Shared CSS = the common block plus any page-local additions (all are
    # class-scoped, so carrying them in the shared file is safe).
    base = styles[SLUGS[0]]
    merged = base
    for slug, css in styles.items():
        if css == base:
            continue
        extra = css[len(base):] if css.startswith(base) else None
        if extra is None:
            raise SystemExit("{}: CSS diverges from the shared base".format(slug))
        merged += extra

    header = (
        "/* BrandSure — service page styles (shared by all 4 pillar pages).\n"
        "   Generated by wp/tools/build-wp-content.py from the static pages.\n"
        "   Element-level resets are scoped to .bs-service so the theme header\n"
        "   and footer are untouched. Edit the source HTML, then re-run. */\n"
    )
    (ASSETS / "brandsure-services.css").write_text(
        header + scope_css(merged) + "\n", encoding="utf-8"
    )

    (CONTENT / "seo.json").write_text(
        json.dumps(seo, indent=2, ensure_ascii=False) + "\n", encoding="utf-8"
    )

    print("CSS   :", (ASSETS / "brandsure-services.css").stat().st_size, "bytes")
    for slug in SLUGS:
        p = CONTENT / (slug + ".blocks.html")
        print("blocks:", slug, p.stat().st_size, "bytes")
    print("page-local scripts found:", [s for s, _ in extra_js])
    (CONTENT / "page-local-scripts.txt").write_text(
        "\n\n".join("/* {} */\n{}".format(s, j) for s, j in extra_js), encoding="utf-8"
    )


if __name__ == "__main__":
    main()
