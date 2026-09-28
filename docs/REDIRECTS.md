# 301 Strategy

*What redirects when the new theme replaces the old site. Implemented in `inc/redirects.php`
(theme-level, so it deploys and rolls back with the theme) and mirrored at the server/CDN level
once verified.*

---

## 1. The principle

The old site's four service URLs and `/services/` are ranking assets with their own search
intent. **They keep their slugs and are never redirected.** Only URLs that have genuinely moved
get a 301. Everything else either stays put or, if it never existed publicly, needs no rule.

A 301 is permanent and cached hard by browsers. Each rule below therefore has to be right the
first time, so the list is deliberately short.

---

## 2. The redirect table

| # | From | To | Type | Reason |
|---|---|---|---|---|
| 1 | `/pricing/` | `/plans/` | **301** | The page moved and was renamed. The only content redirect in this build (Architecture v3 §4). |
| 2 | `/pricing` , `/pricing/index.html` , `/pricing.html` | `/plans/` | **301** | Same target; covers the un-slashed form and the two static-file forms the old build served. |

That is the complete list of content redirects. Matching is case-insensitive, and the query
string is carried across — `/pricing/?utm_source=deck` reaches `/plans/?utm_source=deck`, so a
campaign still pointing at the old URL keeps its reporting.

One rule that is not a content redirect:

| # | From | To | Type | Reason |
|---|---|---|---|---|
| 3 | `/sitemap.xml` | `/wp-sitemap.xml` | **301** | WordPress's sitemap index lives at `/wp-sitemap.xml`. `/sitemap.xml` is where people and tools look first. |

---

## 3. Explicitly NOT redirected

| URL | Status |
|---|---|
| `/services/` | Stays. Becomes the "Other Services" index. |
| `/furniture-growth-marketing-india/` | Stays. Same slug, same intent, rebuilt page. |
| `/furniture-business-consulting-india/` | Stays. |
| `/furniture-product-design-services-india/` | Stays. |
| `/furniture-labour-hiring-india/` | Stays. |
| `/about/` | Stays. |
| `/how-it-works/` | Stays. |
| `/blog/`, `/blog/<post>/` | Stay, with existing post slugs preserved. |
| `/privacy-policy/`, `/terms-and-conditions/`, `/cookie-policy/`, `/disclaimer/` | Stay. |

New URLs with no predecessor — `/plans/` (target of rule 1), `/digital-partner/`,
`/growth-partnership/`, `/proof/`, `/start/`, `/built-by-brandsure/` — need no rule.

---

## 4. Canonical form

Enforced once, at the server or CDN layer, before any application rule runs:

* `https://` only — HTTP → HTTPS, 301
* `brandsure.in` only — `www.brandsure.in` → apex, 301
* trailing slash on every page URL (WordPress default), no double slashes
* lowercase paths

These are host-level rules, not per-page redirects. They must not chain: a request for
`http://www.brandsure.in/pricing` should reach `https://brandsure.in/plans/` in **one** hop, not
three. Verify with `curl -sI -L` and count the hops.

---

## 5. `noindex`, not redirect

| URL | Rule |
|---|---|
| `/built-by-brandsure/` | `noindex, follow`, and **crawlable** — see the correction below. It is a referral surface reached from client websites, not a search surface. Its `?ref=` variants must not be indexed or canonicalised into separate pages — canonical points at the bare path. |
| Any `?plan=` / `?interest=` / `?ref=` / `utm_*` variant of a public page | Canonical points at the clean path so query-string traffic never splits a ranking. |
| `go.brandsure.in` (the whole host) | `noindex` — already the case. It is not part of this build and no public page links to it. |

**Correction, made in Chat 11 when this was implemented.** An earlier version of this document
said `robots.txt` should disallow `/built-by-brandsure/`. That was wrong and is not built: a
disallowed URL is never fetched, so the `noindex` on it is never read, and Google may index the
bare URL anyway on the strength of the inbound links from client websites. It also cancels the
`follow` half of the rule, which is the half worth keeping — the links out of that page lead to
`/start/`. `robots.txt` therefore allows everything; the `noindex, follow` tag does the work, and
the page is kept out of the XML sitemap so the two signals agree. Both read one list,
`bs_noindex_slugs()` in `inc/seo.php`.

---

## 6. Cutover checklist

Run before the new theme is switched on, and again immediately after:

1. Crawl the live site and export every indexed URL and its current status.
2. Confirm every URL in that export either (a) exists in the new build at the same path, or
   (b) has a rule in §2. Anything in neither category is a gap — resolve it before cutover, not
   after.
3. Confirm `/pricing/` returns 301 → `/plans/` → 200, one hop.
4. Confirm the four service URLs and `/services/` return 200 with no redirect at all.
5. Confirm `robots.txt` allows everything and names the sitemap, and that `/wp-sitemap.xml`
   lists the primary journey, `/services/` + the four service pages, the blog and the legal
   pages — and does **not** list `/built-by-brandsure/`. Confirm there is no author or
   taxonomy sitemap: those providers are removed, because they would list archive pages that
   are thin duplicates of the blog index.
6. Re-verify the canonical tag on every template.
7. Submit the new sitemap and request re-crawl of `/plans/`.

No old file is deleted at cutover. The previous theme and plugin remain installed and inactive
so the switch can be reversed in one click.
