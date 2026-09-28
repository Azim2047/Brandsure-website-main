# `components/` — one implementation per component

Every piece of markup in the theme is written here, once. Templates call components; templates
do not contain markup of their own beyond a section loop.

**The rule that killed the old build:** no template may contain its own header, its own footer,
or its own copy of the CSS. That is how the previous install ended up with the same page in four
places.

A component takes an array of props and echoes markup. It reads no global state, decides no
copy, and hard-codes no colour — colours come from `tokens.css`, copy comes from `data/` (or
from `the_content()` on an editable page).

## Planned set (Chat 1 unless noted)

**Shell**
* `site-header.php` — logo, five nav items, the Start button, the mobile menu
* `site-footer.php` — Other Services, Company, Legal columns
* `sticky-bar.php` — mobile WhatsApp + Call bar, on every public page

**Structure**
* `section.php` — the section wrapper: spacing, background variant, container width
* `hero.php`
* `cta.php` — the page-foot route to `/start/`, on every page so none is a dead end
* `trust-strip.php`

**Journey and proof**
* `chain.php` — the one chain, 8 nodes, lane-coloured. There is no second diagram.
* `video-block.php` — poster, tap-to-load, player events. Refuses any video with `public: false`.
* `gate-card.php` — the four-number card that ends a blog post or stands in for a missing video
* `plan-card.php`, `gauge.php`, `compare-table.php` (Chat 4)
* `nested-boxes.php`, `swimlanes.php`, `tick-row.php`, `stage-list.php` (Chat 6)
* `founder-card.php`, `credential-stat.php`, `case-result.php` (Chat 7) — `case-result.php`
  renders the client result and its mandatory footnote together, so the footnote cannot be
  dropped
* `accordion.php` — FAQ; the only thing that may trigger `FAQPage` schema

**Editable-page furniture**
* `service-enquiry.php` — the per-service WhatsApp path
* `gp-bridge.php` — the one quiet Growth Partnership bridge. Never rendered on
  `/furniture-labour-hiring-india/`.
