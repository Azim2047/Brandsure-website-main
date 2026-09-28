# Content & Data Architecture

*How content lives in `brandsure-v2`, and the mechanism that makes double-editing structurally
impossible rather than merely discouraged. Implements Override 4 and Architecture v3 §7.*

---

## 1. The rule

> **Every page has exactly one editable copy of its content, in exactly one place.**

Two ownership classes, and a page is in exactly one:

| | CONTROLLED | EDITABLE |
|---|---|---|
| Copy lives in | `wp-content/themes/brandsure-v2/data/**.json`, versioned in git | The WordPress database, via the block editor |
| Who edits | A developer, through a commit | Backend staff, in wp-admin |
| Block editor | **Disabled** for that page | Enabled |
| JSON data file | Required | **Forbidden** |
| Deploys with | The theme | Nothing — it is live content |
| Pages | Home, Plans, Digital Partner, Growth Partnership, How It Works, Proof, Start, Built-by-BrandSure, Services index | The four service pages, About, Blog + posts, the four legal pages |

The old install failed exactly here: the same copy existed simultaneously in `static/*.html`,
`content/*.blocks.html`, `main-pages.json` and the database. **None of those four mechanisms is
reproduced in this build.** `render-static.php`, the `static/` directory, the `.blocks.html`
seeding and `main-pages.json` are all retired.

---

## 2. Directory layout

```
wp-content/themes/brandsure-v2/
├── style.css                    theme header + the import chain, no page CSS
├── functions.php                setup, enqueues, includes — no markup
├── index.php                    fallback
├── assets/
│   ├── css/tokens.css           THE design tokens (BSU Theme File) — nothing else defines a colour
│   ├── css/brandsure.css        component styles, built on the tokens (Chat 1)
│   ├── js/brandsure.js          vanilla JS: nav, sticky bar, video, gate, events (Chat 1)
│   └── img/icons.svg            one inline SVG sprite
├── components/                  ONE implementation per component, shared by every template
├── data/                        THE ONLY PLACE CONTROLLED COPY LIVES
│   ├── site.json                nav, footer, contact, WhatsApp templates
│   ├── commercial.json          PUBLIC commercial facts only
│   ├── plans.json               the three plans
│   ├── proof.json               credentials, the case, client sites
│   ├── videos.json              video ids, durations, public/gated flags
│   ├── faq.json                 keyed by page, so schema emits only where rendered
│   └── pages/*.json             section order + copy, one file per controlled page
├── inc/                         PHP behaviour, no markup
└── templates/                   page templates — section loops only
```

Two hard rules on this tree:

1. **No template contains a header, a footer or a copy of the CSS.** Templates call components.
   Components are the only place markup is written.
2. **`data/` never contains a private commercial fact.** The private list in
   `SOURCE-OF-TRUTH.md` §5.2 does not exist anywhere in this theme, including in comments.
   The theme has no need for those values, so it does not hold them.

---

## 3. How a CONTROLLED page renders

```
WordPress page (slug + template assignment, empty post_content)
        │
        ▼
templates/page-<slug>.php
        │  bs_data('pages/plans')      ← inc/data.php: read, validate, cache in a transient
        ▼
  foreach section → components/<type>.php
        │
        ▼
  header.php · footer.php · sticky-bar.php    (shared, always)
```

The WordPress page exists so that the URL, the menu item and the SEO fields have somewhere to
live. Its `post_content` stays empty and its editor is disabled — there is nothing to edit
there, so there is nothing to drift.

`inc/data.php` responsibilities: read the JSON, fail loudly in `WP_DEBUG` on a malformed or
missing file, validate against the section-type registry, and cache the parsed result in a
transient keyed by the theme version so a deploy invalidates it.

---

## 4. How an EDITABLE page renders

```
WordPress page (real post_content, edited in wp-admin)
        │
        ▼
page.php  /  templates/page-service.php
        │
        ├── components/header.php            shared
        ├── the_content()                    ← the ONLY copy of this page's words
        ├── components/service-enquiry.php   shared, config from data/site.json
        ├── components/gp-bridge.php         shared, omitted on labour hiring
        └── components/footer.php            shared
```

The theme supplies the **shell and the conversion furniture**; WordPress supplies the **words**.
The enquiry block and the bridge are components, not editor content, precisely so a staff edit
can never break the routing or accidentally publish a commercial fact.

Reusable blocks (`wp_block`) are permitted for repeated editorial furniture — an author edits
the pattern once and every page updates. They are still a single copy, so they satisfy the rule.

---

## 5. The enforcement mechanism — `inc/content-ownership.php`

One registry, consulted by everything else:

```php
return [
  // slug                        => ownership
  ''                             => 'controlled',   // front page
  'plans'                        => 'controlled',
  'digital-partner'              => 'controlled',
  'growth-partnership'           => 'controlled',
  'how-it-works'                 => 'controlled',
  'proof'                        => 'controlled',
  'start'                        => 'controlled',
  'built-by-brandsure'           => 'controlled',
  'services'                     => 'controlled',

  'about'                             => 'editable',
  'furniture-growth-marketing-india'  => 'editable',
  'furniture-business-consulting-india' => 'editable',
  'furniture-product-design-services-india' => 'editable',
  'furniture-labour-hiring-india'     => 'editable',
  'privacy-policy'                    => 'editable',
  'terms-and-conditions'              => 'editable',
  'cookie-policy'                     => 'editable',
  'disclaimer'                        => 'editable',
];
```

Four things hang off it, and together they close the loop:

1. **Editor lock.** `use_block_editor_for_post` and `user_can_richedit` return `false` for a
   controlled page. wp-admin shows a notice naming the JSON file that owns the copy, so an
   editor who goes looking is told where to file the request rather than left guessing.
2. **Content lock.** On save of a controlled page, `post_content` is forced back to empty. Even
   a direct database edit or an importer cannot create a second copy that would then render.
3. **Template lock.** A controlled slug is bound to its template in code
   (`template_include`), not by the page-attributes dropdown, so the binding cannot be changed
   from the admin.
4. **Guard check** (`inc/guard.php`, run in CI): fails the build if a `data/pages/<slug>.json`
   exists for an editable slug, if a controlled slug has no data file, or if any controlled page
   in the target database has non-empty `post_content`.

Rules 1–3 make duplication impossible at runtime. Rule 4 catches it at deploy time if someone
edits the tree by hand.

---

## 6. `data/` file contracts

Skeleton files are created in this chat with their shape and their required keys, and are filled
in the chat that builds the page they serve. Values below marked `""` are deliberately empty
until then — an empty string is not a placeholder to be invented, it is a slot for approved copy.

| File | Holds | Filled in |
|---|---|---|
| `site.json` | brand, contact, header nav, footer columns, WhatsApp templates and prefill parameter names, sticky-bar labels | Chat 1 |
| `commercial.json` | **public** commercial facts only, one key per fact, each with `value`, `note`, `public: true`, and the Numbers-Sheet reference (`A1`, `A2`, `A4`) it came from | Chat 1 |
| `plans.json` | the three plans: name, slug, price display or `null`, promise line, chain-node count, badge, CTA label, CTA parameter (`plan=` / `interest=`), `disclosure` level | Chat 4 |
| `proof.json` | credentials, the client case with its mandatory footnote, the client-site array used by `/proof/` and `/built-by-brandsure/` | Chat 7 |
| `videos.json` | one entry per video: id, title, duration, `public` flag. Videos 2, 4 and 5 are present with `public: false` and **no id**, so the theme cannot embed them even by mistake | Chat 1 |
| `faq.json` | questions keyed by page slug. `schema.php` emits `FAQPage` only for a slug present here *and* rendering a visible FAQ block | Chat 7 |
| `pages/*.json` | `sections: [{ type, ...props }]` — an ordered list of component invocations plus their copy | per page |

`commercial.json` is the single website-side mirror of Supabase `commercial_facts`. Guard rule 5
compares them, so the AI, the private funnel and the website provably quote the same numbers.

---

## 7. Migrating the old copy — once, and only once

Starting content for an editable page lives in `migration/<slug>.html` as
block markup, is imported into WordPress **once**, and is deleted in the same
commit that confirms the import. A seed left behind will eventually be edited
by someone expecting the site to change, so `tools/guard.php` lists any that
are still present as a warning. `migration/README.md` has the import command.


For each EDITABLE page, the old site's useful material is imported **one time**, at build, and
then never referenced again:

* body copy, headings and FAQs → WordPress `post_content`
* images → the media library
* title, meta description, canonical, OG and JSON-LD → **derived**, never entered

**There are no per-page SEO fields, and there will not be.** A plugin offering a title and
description box per page would put a second editable copy of the page's own words behind a tab
nobody opens, and the two would drift within a month — which is Override 4 with the serial
numbers filed off. So `inc/seo.php` reads the description out of the page: the excerpt if an
editor wrote one, otherwise the opening prose. `inc/schema.php` does the same for structured
data, including reading the `FAQPage` questions back out of the `<details>` blocks in the
published content. Controlled pages take both strings from their `data/pages/*.json`, where the
rest of their copy already lives; guard rule R10 fails the build if one is missing.

After the import the old files are dead references. They are not read at runtime, not shipped
with the theme, and not kept in sync. The `wp/` directory and the root `*.html` files in this
repository stay exactly as they are, as an archive.

For each CONTROLLED page nothing is imported wholesale. Old pricing, package names, commercial
terms, navigation, Growth Partnership explanation and homepage positioning are all discarded by
rule.

---

## 8. Performance

Full-page cache at the host. JSON parsed once per deploy into a transient. Two static assets
(`brandsure.css`, `brandsure.js`) with far-future cache headers, versioned by theme version.
Inline SVG sprite, no icon font, no web-font hosted third-party call beyond the one Inter
stylesheet (self-hosted preferred). YouTube behind a poster image, loaded on tap only.

Budget: LCP under 1.5 s on 4G, under 600 KB excluding the video iframe. No jQuery. No build
step required to serve the site.
