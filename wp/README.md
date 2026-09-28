# BrandSure — WordPress site infrastructure

Turns the static pages into real WordPress content the admin team can manage
under **Pages** and **Posts**, without changing how the site looks. Covers the
four "Other Services" pages, the blog, and the company/legal pages.

The static `*.html` files at the repo root stay the source of truth for markup.
`wp/tools/build-wp-content.py` re-frames them for WordPress; it never rewrites copy.

## What is here

```
wp/plugin/brandsure-services/        <- install as a normal plugin
  brandsure-services.php             bootstrap, service-page assets/SEO/schema, SEO metabox, page registry
  seo-defaults.php                   generated: original title/description/canonical/OG/JSON-LD
  includes/class-blog.php            blog templates, image sizes, listing query, pagination, schema
  includes/class-legal.php           legal pages + native Settings -> Privacy wiring
  includes/class-footer.php          discreet Blog/policy footer links (menu, shortcode, fallback)
  includes/class-header.php          the one global header used by every page
  includes/class-mainsite.php        services hub / how-it-works / pricing: body, schema, behaviour
  includes/class-links.php           repairs for pages pasted in as whole static documents
  includes/class-copy.php            public-copy corrections as exact-match replacements
  includes/class-social.php          Instagram gallery, team photo, Settings -> BrandSure
  includes/class-cli-command.php     guarded WP-CLI migration (never runs on a page load)
  templates/blog-archive.php         listing: posts page + category/tag/author/date archives
  templates/blog-single.php          single article
  assets/brandsure-services.css      generated: the shared design system (tokens + components)
  assets/brandsure-blog.css          blog cards, pagination, long-form typography
  assets/brandsure-nav.css/.js       the global header
  assets/brandsure-services.js       generated: WhatsApp tracking, sticky pill, requirement builder
  assets/brandsure-mainsite.js       pricing plan finder + calculator, sticky pill, tracking
  assets/brandsure-reveal.js         the .rv scroll reveal, on every page
  content/<slug>.blocks.html         generated: page bodies as Gutenberg blocks
  content/main-pages.json            generated: main-site SEO values and JSON-LD
  includes/class-enquiry.php         the homepage callback form: stores + emails each request
  assets/brandsure-mainsite.js       plan finder, calculator, sticky pill, tracking, callback form

wp/theme/brandsure-theme/            <- install as a theme; replaces the static-file theme
  style.css                          generated: the design system, site-wide
  header.php  footer.php             the one header and footer
  page.php  front-page.php           pages, including the homepage
  singular.php  index.php  404.php   fallbacks
  functions.php                      supports, enqueues, content buffering

wp/reference/brandsure-wordpress-theme/   the outgoing theme's PHP, for the record
wp/tools/build-wp-content.py         generator for the service pages
wp/tools/build-main-pages.py         generator for the homepage, services hub, how-it-works, pricing
wp/tools/build-theme-css.py          generator for the theme stylesheet
wp/tools/build-legal-pages.py        generator for the four policy pages
```

## Architecture

| Concern | Where it lives |
| --- | --- |
| Header, footer, menu | `brandsure-theme`, which calls `wp_body_open()` so the plugin's one header renders |
| The menu definition | `brandsure_nav_items()` in the plugin — one array, no per-template copy |
| Site-wide CSS | the theme's `style.css`, once |
| Service-page CSS and JS | this plugin, enqueued only on those four pages |
| Page copy and sections | page content, one Custom HTML block per `<section>` |
| Homepage / hub / how-it-works / pricing bodies | shipped with the plugin, overridable by editing the page |
| SEO title / description / canonical | Rank Math, which owns the head on this site |
| Schema (JSON-LD) | this plugin — `brandsure_services_schema`, `brandsure_main_schema` |
| Callback requests | wp-admin → **Enquiries**, plus an email |

Nothing is duplicated per page: the CSS, the header and the footer each exist once.

## Deploy

1. Copy `wp/plugin/brandsure-services/` to `wp-content/plugins/brandsure-services/`.
2. Activate **BrandSure Service Pages** in wp-admin → Plugins.
3. Create the pages, if they are not already there. On brandsure.in they were
   already created as drafts (IDs below), so `migrate` will report them as
   skipped — that is the expected result, not an error:

   | Slug | Page ID | Status |
   | --- | --- | --- |
   | `furniture-product-design-services-india` | 225 | published |
   | `furniture-growth-marketing-india` | 226 | published |
   | `furniture-business-consulting-india` | 227 | published |
   | `furniture-labour-hiring-india` | 228 | published |
   | `blog` | 230 | published |
   | `disclaimer` | 231 | published |
   | `cookie-policy` | 232 | published |
   | `terms-and-conditions` | 233 | published |
   | `privacy-policy` | 234 | published |
   | `how-it-works` | 255 | published |
   | `pricing` | 256 | published |
   | `services` | 257 | published |
   | `start` (the homepage) | 260 | published, `page_on_front` |

   The front page (42) and About (194) were created earlier by pasting the whole
   static document into the page body; they are not managed by `migrate`.

   After publishing the Blog page, point WordPress at it:
   `wp option update page_for_posts 230`

   To (re)create them anywhere else:

   ```sh
   wp brandsure services status              # what exists now
   wp brandsure services migrate --dry-run   # what would be created
   wp brandsure services migrate             # create as drafts
   ```

   `migrate` matches on slug and skips anything that already exists, so it is
   safe to re-run and cannot produce a duplicate or a `-2` permalink.
4. Review each page, then publish.

## Notes

* **The pages are deliberately top level.** Giving them a real `post_parent` of
  `/services/` would move the permalink to `/services/<slug>/` and break the
  live URL. The `/services/` relationship is expressed in the menu and in the
  breadcrumb schema instead.
* **Element resets are scoped.** The static pages owned the whole document and
  reset `body`, `a`, `p` and `h1`–`h4` globally. Those rules are re-pointed at
  `.hero`, `.band` and `.wapill` — the only top-level elements a service page
  has — so the theme's header and footer are unaffected. Scoping to the section
  classes rather than a wrapper `<div>` keeps every Custom HTML block balanced,
  which is what the block editor validates. The design tokens stay on `:root`.
* **Scripts moved out of the page body.** WordPress strips `<script>` from post
  content, so the three inline scripts ship in the plugin's JS instead. Each one
  exits early when its markup is absent, so one file serves every page.
* **Canonicals point at `https://brandsure.in`.** The static files were authored
  against `www.brandsure.in`, but the live site serves the bare domain, so a
  `www` canonical would point at a redirect.

## Regenerating

After editing any static page:

```sh
python3 wp/tools/build-wp-content.py
```

Then re-paste the changed `content/<slug>.blocks.html` into the page, or delete
and re-run `migrate` for a page that has not been hand-edited yet.

For the three main-site pages, re-run their generator instead and upload the
plugin — nothing has to be pasted anywhere:

```sh
python3 wp/tools/build-main-pages.py
```


## The theme

`wp/theme/brandsure-theme/` replaces `brandsure-wordpress-theme`, whose PHP is
kept in `wp/reference/` for the record.

**What the old theme did.** It was not a theme so much as a static-file server.
`inc/render-static.php` did `readfile($path); exit;`, and `functions.php` hooked it
on `template_redirect` at **priority 0** for nine routes: `/`, `/about/`,
`/services/`, `/how-it-works/`, `/pricing/` and the four `furniture-*` pages. It
exited before WordPress rendered anything, so on those nine URLs there was no
`wp_head()`, no `wp_body_open()`, no `the_content()` and no `wp_footer()`.

Everything that was wrong with the site followed from that one line:

| Missing hook | Consequence |
| --- | --- |
| `the_content` | every filter in this plugin was dead — the global header, the copy corrections, the `.html` link repair, the About sections, the page bodies for 255–257 |
| `wp_head` | Rank Math emitted nothing: no title, description, canonical, OG or schema on the nine main pages |
| `wp_head` / `wp_footer` | no analytics of any kind. The served HTML contained no gtag, GTM, Clarity or Meta pixel, so ad spend on those landing pages was unmeasured |
| — | the pages were shadowed: `/pricing/` served an August file, not page 256 |
| `page.php` fall-through | any page **not** in its map printed a bare "Page not found" with a 404 header, which is what broke the four legal pages |

**What the new theme does.** Renders pages the ordinary way. Nothing in it
intercepts a request.

| File | Role |
| --- | --- |
| `style.css` | generated by `wp/tools/build-theme-css.py` — the design system, once |
| `header.php` | `<head>` + `wp_head()`, then `wp_body_open()`, which is what finally lets the plugin's global header render |
| `footer.php` | the `.ft` footer, the WhatsApp pill, `brandsure_footer_links()`, `wp_footer()` |
| `page.php` | the loop and nothing else — the designs own their headings |
| `front-page.php` | `page.php`; the homepage is a normal page |
| `singular.php`, `index.php`, `404.php` | fallbacks. The blog has its own templates in the plugin |
| `functions.php` | supports, enqueues at priority 5, `brandsure_theme_render_content()` |

The footer and the pill are **conditional**. Page bodies are at different stages of
migration — some still carry their own footer and pill from when each page was a
self-contained document, others carry neither — so `brandsure_theme_render_content()`
buffers the content, records what it found, and `footer.php` supplies only what is
missing. No page ends up with two footers and none with none.

### The stylesheet

`build-theme-css.py` merges the two variants in the statics with **base precedence**:
the sheet shared by `services.html` / `how-it-works.html` / `about.html` wins, and
only selectors it does not define are taken from `pricing.html`. Base precedence is
not arbitrary — `.hero__sub` is centred at 34ch in the base and left-aligned at 58ch
in pricing's, and the base version is what `/about/` renders with.

Two kinds of rule are **unioned** rather than picked, because picking would break the
pricing page:

- `:root` — pricing's sheet defines twenty tokens the base does not (the whole
  `--fs-*`/`--fw-*` scale, plus the surface and state colours) and its own components
  reference them. No token has a conflicting value, so the union is unambiguous.
- `@media` blocks with the same condition — otherwise `.plans` stays a three-column
  grid on a phone.

The four service pages keep the plugin's `assets/brandsure-services.css`: its `.hero`
is a white band while the main-site `.hero` is the dark homepage hero. The theme
enqueues at priority 5 so the plugin's sheet still wins on `:root`, where it sets
`--maxw:1080` against the theme's 1140.

For the same reason the theme does **not** override the heroes' top padding to match
the taller header. Any rule specific enough to beat the page-level stylesheets would
also add 140px of empty space above every service page. The 70px the heroes reserve
already clears the 84px header, since they add 40–110px of their own on top.

### Carried over from the August build

`update-theme.js` applied design decisions when it baked the old theme's copies, and
they exist in no source file. They are reproduced rather than reverted, because they
are what the site has been running:

- the real logo (`brandsure-web-logo.png`, 160×43) in place of the lettermark
- header height 84px, `scroll-padding-top:96px`, nav wrap 1320px
- the header **stays dark** on scroll instead of flipping to white
- the `.ui-icon` SVG helper

`brandsure_nav_brand()` resolves the logo in three steps: the Customizer logo first,
because that is where an owner expects to change it; then the media-library upload the
site already serves; then the lettermark, so the header is never empty.

### Reverting

Appearance → Themes → activate `brandsure-wordpress-theme`. Instant, no data loss.
Then Settings → Reading → Homepage back to the old page if needed.


## The homepage

Two homepages exist:

`page_on_front` is **260** (`start`), built from `index.html`.

Page **42** (`home`) held the previous front page and is now unlinked. Its body is
a navy `#0F1A40` / mint `#00E5A0` document in Fraunces + DM Sans with an anchor-only
nav — a design that appears nowhere else on the site and contradicts
`BSU Theme File.docx`. Worth knowing: **it was never actually served.** The old
theme's `front-page.php` read `static/index.html`, so page 42's content has been
dead since that theme was activated.

It is kept rather than overwritten because its 60 KB body exists nowhere else — not
in this repo, not in any static file — and reverting the homepage is then one
setting.

A page acting as the front page is served at `/`, and WordPress redirects its own
slug there, so `/start/` never shows up as a second URL.

### The callback form

`index.html`'s "Prefer a call instead?" form had **no endpoint**. It validated
the fields, pushed a `dataLayer` event, wrote the values to the browser console
and showed "Got it. We'll call you." — so a real enquiry was discarded while the
visitor was told they would be called.

`includes/class-enquiry.php` gives it a real one. Each request is stored as a
private `brandsure_enquiry` post (wp-admin → **Enquiries**, with the mobile
number as a `tel:` link) and emailed to the site's admin address. The success
panel now appears only after the request has actually been accepted; a failure
says so and points at WhatsApp instead of silently claiming success.

To send enquiries somewhere else as well, hook `brandsure_enquiry_received`; to
change who is emailed, filter `brandsure_enquiry_recipients`.


## Main-site pages — services hub, how it works, pricing

`/services/`, `/how-it-works/` and `/pricing/` are real published pages (255,
256, 257) whose **body is supplied by the plugin**, from
`content/<slug>.blocks.html`. The page is deliberately empty in the editor, and
the **BrandSure page body** box in the sidebar says so.

Why this way rather than pasting the markup in:

* the static page stays the single source of truth — edit the `.html`, re-run
  the generator, upload the plugin;
* no 40–75 KB of markup has to be retyped through an API, which is how a live
  page gets silently corrupted;
* `<script>` and `&` survive, because a real `.js` file is not subject to the
  entity-escaping that happens to markup pasted into a page.

It is **self-deactivating**. Put anything in the page body and it takes over
completely: `brandsure_main_inject()` returns early as soon as the stored
content is non-empty or already contains a `pagehero`. So the owner can paste
the real markup in later and nothing needs to be un-hooked.

What each page keeps from its static original:

| Concern | Where it comes from |
| --- | --- |
| Page stylesheet | the first block of the generated body, as the front page and About also do |
| Sections | one Custom HTML block per `<section>`, in order |
| Footer and WhatsApp pill | the last two blocks |
| Navigation | **not** in the body — the global header supplies it |
| Behaviour | `assets/brandsure-mainsite.js` (sticky pill, WhatsApp tracking, plan finder, worth-it calculator) |
| Scroll reveal | `assets/brandsure-reveal.js`, loaded on every page |
| JSON-LD | `content/main-pages.json`, emitted by `brandsure_main_schema()` |

The generator refuses to write a page that has more than one `<h1>`, that leaks
the legacy header or an inline script, or that contains the partnership
percentage or any of the banned commercial phrasings.

**Titles and descriptions still have to be set in Rank Math**, which owns the
document head on this site. The values from the static pages are in
`content/main-pages.json` under `title` and `description`.


## Pages that were pasted in as whole documents

The front page (42) and About (194) hold a complete static HTML document in
`post_content`. That left two defects that editing the source files cannot fix,
so `includes/class-links.php` repairs them on output and leaves the stored
content untouched:

* **`.html` links.** Every link still points at a filename — `how-it-works.html`
  is a 404 on a site serving pretty permalinks. `brandsure_links_rewrite()`
  maps each one to its real URL.
* **Escaped scripts.** The ampersands inside the pasted `<script>` were
  entity-escaped on save (`&&` became `&#038;&#038;`). Character references are
  not decoded inside a script element, so the file is a syntax error and none of
  it runs — including the observer that makes `.rv` sections visible at all.
  `brandsure_links_repair_scripts()` decodes `&` back inside script elements
  only, and `assets/brandsure-reveal.js` is a second line of defence: it runs
  the same observer from a real file, and adding the class twice is harmless.
* **Placeholder social links.** The pasted footers link to
  `https://www.instagram.com/` and `https://www.linkedin.com/`. Fill the two
  URLs in **Settings → BrandSure** and `brandsure_links_social()` substitutes
  them. With nothing configured the links are left exactly as the page has them
  — no URL is guessed.

All three are exact-match and therefore self-deactivating: re-pasting a cleaned
file leaves them with nothing to do.


## Blog

Native WordPress Posts — nothing is hard-coded in PHP. The plugin only supplies
presentation, through the `template_include` filter, so the theme keeps
ownership of the header and footer and no theme file is added or edited.

| View | Template | Notes |
| --- | --- | --- |
| `/blog/` | `templates/blog-archive.php` | listing with cards + pagination |
| category / tag / author / date | `templates/blog-archive.php` | same template, heading swaps |
| single article | `templates/blog-single.php` | featured image, meta, prev/next |

**One setting is required after install:** Settings → Reading → *Posts page* must
be set to the Blog page, so WordPress owns `/blog/` and `/blog/page/2/`
natively. `wp brandsure services status` warns while it is unset, and
`wp option update page_for_posts <id>` sets it. Until then the plugin still
renders the listing from a secondary query as a fallback.

### Adding an article

Posts → Add New. Title, content, **Featured image**, and a **Category** are all
the card needs. The excerpt is used for the card summary and falls back to the
opening lines if left empty. Draft, schedule and publish all behave normally.

Featured images are cropped to `brandsure-card` (800×500) for cards and
`brandsure-wide` (1200×630) for the article header and social sharing — upload
at 1600×1000 or larger. Existing images need
`wp media regenerate --image_size=brandsure-card --image_size=brandsure-wide`.

### SEO

Rank Math is active on this site, so it owns titles, descriptions, canonicals,
Open Graph and Article schema, and it generates the XML sitemap — which picks up
posts and pages automatically. The plugin therefore emits **no** competing tags
or schema while an SEO plugin is detected; its own output is a fallback for a
site without one. Each view renders exactly one `<h1>`.

## Company and legal pages

| Page | Slug |
| --- | --- |
| Privacy Policy | `/privacy-policy/` |
| Terms & Conditions | `/terms-and-conditions/` |
| Cookie Policy | `/cookie-policy/` |
| Website Disclaimer | `/disclaimer/` |

Written as native Gutenberg blocks (headings, paragraphs, lists) so
non-technical staff can edit them directly. Content starts at H2 because the
H1 is the page title. Regenerate with `python3 wp/tools/build-legal-pages.py`.

The plugin points **Settings → Privacy** at the Privacy Policy page once, using
WordPress's own `wp_page_for_privacy_policy` option, and registers suggested
privacy text under **Tools → Privacy**. It never displaces a privacy page an
administrator has already chosen.

Only company details that already appear on the site are used — company name,
CIN, `info@brandsure.in`, the published phone number and "Bengaluru, Karnataka,
India". No street address, GSTIN, licence or additional email is invented, and
no commercial promise or partnership percentage appears.

## Footer links

`includes/class-footer.php` adds discreet Blog + policy links three ways, so it
works whatever the theme does — and never touches the primary navigation:

1. appended to any nav menu whose theme location contains `footer`, skipping
   links already in the menu;
2. the `[brandsure_legal_links]` shortcode, for a footer widget or block;
3. a small strip printed in `wp_footer`, used only when neither of the above
   already rendered the links on that request.

A policy page that is still a draft is left out, so the footer never links to a
404. `brandsure_footer_render_strip` (filter) disables the fallback strip if the
links get placed in the theme instead.


## About page — Instagram gallery and team photo

The live `/about/` page is a single Custom HTML block holding the whole pasted
document. Rather than rewrite that 48 KB blob, `includes/class-social.php`
injects three sections into the rendered content just before `</main>`:

1. **Behind BrandSure** — the Instagram gallery
2. **The Team** — one group photograph
3. the closing **Talk to Us About Your Furniture Business** CTA

The injection is **self-deactivating**: the moment the page content itself
contains `id="behind"`, the filter steps aside. So pasting the updated
`about.html` into the page later is safe and changes nothing on screen. The
founder hero, story, proof points and photo frame are untouched either way.

### Editing it — Settings → BrandSure

| Field | Notes |
| --- | --- |
| Instagram profile URL | One setting, reused by the gallery and every "Follow us" link. No handle is hard-coded anywhere. Leave empty and no Instagram links render. |
| Graph API access token | Optional. See below. |
| Gallery cards ×6 | Image (Media Library picker or a URL), Instagram post URL, alt text. A card without an image is skipped. |
| Team photograph | Media Library picker or URL. ~1600×900 works best. |
| Team caption | Optional, shown under the photo. |
| Team alt text | Falls back to "The BrandSure team". |

### How Instagram is connected

Resolved in this order, and Instagram HTML is **never scraped**:

1. **Official Instagram Graph API** when a token is saved —
   `graph.instagram.com/me/media`, 5-second timeout, result cached in a
   transient for one hour so page loads never wait on Meta.
2. **The six admin-managed cards** — used when there is no token, or whenever
   the API call fails.
3. **Nothing** — if neither is configured the section renders empty (with a
   note visible only to administrators). The page still loads normally.

A failed API call is cached for 10 minutes, so a bad or expired token cannot
slow every request. Saving settings clears the cache.

### Styles

`assets/brandsure-about.css` only references tokens the page already defines
(`--blue`, `--aqua`, `--border`, `--r`, `--shadow`, `--ease`), so it adds
components without a second visual system. CSS Grid, no carousel, no JS
library. Gallery is 2 columns on mobile, 3 from 640px, 6 from 1000px. Images
carry `width`/`height` and `aspect-ratio`, so the gallery reserves its space
and does not shift layout as it loads.


## Global header

`includes/class-header.php` is the **single source of truth** for navigation.
`brandsure_render_header()` is the only function that prints a header, and
`brandsure_nav_items()` is the only place the menu is defined — so the items,
their order, the links and the dropdown cannot vary per template.

### Primary menu — exactly five items

| # | Label | Target |
| --- | --- | --- |
| 1 | How It Works | `/how-it-works/` |
| 2 | Prices | `/pricing/` |
| 3 | Other Services | `/services/` + dropdown |
| 4 | About the Team | `/about/` |
| 5 | Talk to Us | the existing WhatsApp URL (CTA) |

Dropdown: All Other Services (`/services/`), Growth & Marketing, Business
Consulting, Product Design, Labour Hiring Help. Blog is deliberately absent —
it is reached from the footer.

"Other Services" is a `<button>` with `aria-expanded`, and `/services/` is the
first entry *inside* the dropdown. That keeps the toggle accessible instead of
making one element both a link and a menu button.

### How it attaches

Theme files are not editable from here, so the component hooks
`wp_body_open` — the standard hook a theme fires right after `<body>` — with an
automatic `wp_footer` fallback for a theme that does not fire it. The header is
`position:fixed`, so it renders identically from either point; only DOM order
differs. **When theme files become editable, a real `header.php` should simply
call `brandsure_render_header()`** — the source of truth does not move.

### Legacy per-page headers

`/` and `/about/` embed their own `<header class="nav">` in the page content. A
`the_content` filter replaces it with a hidden shell that keeps the element IDs
(`nav`, `burger`, `dd`, `mnav`) the legacy inline script looks up — without
them that script throws on a null element and takes the reveal animations, the
sticky WhatsApp pill and the click tracking with it. Re-pasting a cleaned static
file later leaves the filter with nothing to do.

### Light/dark

Pages that open with the dark full-bleed hero start transparent and go light on
scroll; every other template starts light, decided server-side — white nav text
on a white page would otherwise be invisible. The menu never changes, only the
surface.

`brandsure_nav_page_has_dark_hero()` makes that call, and the same answer
decides the 70px spacer: a dark hero already pads its own top by the header's
height, so adding a spacer there would put a white band above it. The three
main-site pages are dark-hero; the four service pages open on a white band, so
they get the spacer and a light header.

### Active state

Deliberately quiet: a 2px aqua underline plus `aria-current="page"`. "Other
Services" also lights up on any of its four children and on `/services/`.

### Keyboard and dismissal

`aria-expanded` on all three toggles, Down-arrow opens the dropdown and moves
focus into it, `focusout` closes it on tab-away, Escape closes (returning focus
to the toggle), an outside click closes everything, and `pageshow` clears state
so the back/forward cache cannot restore an open menu.
