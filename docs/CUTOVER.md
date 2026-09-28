# Replacing the live site

*How `brandsure-v2` goes live at brandsure.in, and how it comes back off if it has to. Written
in Chat 12; **rewritten in Chat 14 against the real target**, which is not what this document
originally assumed.*

The governing constraint from the brief has not changed: **the old site is not modified.**
Nothing here edits, deletes or patches it.

---

## 0. The target, as it actually is

Read from the live install in Chat 14 rather than assumed. `brandsure.in` is
**WordPress.com Atomic**, blog ID `254504637`.

| | Reality |
|---|---|
| Visibility | **`coming_soon`** — launched once, currently not publicly visible |
| Pages | **0** |
| Posts | **0** |
| Subscribers | none |
| Active theme | a WordPress.com block theme; `brandsure-v2` is **not installed** |
| Active plugins | 14, including Elementor, Rank Math SEO, Microsoft Clarity, Code Snippets, and the old **BrandSure Site Infrastructure v1.7.0** |

**This is a first launch onto an empty install, not the replacement of a live site.** Three
things follow, and they contradict what this document said before:

* There is no old content here to preserve or redirect away from. The old site's pages exist in
  this repository as static HTML and in the wild as indexed URLs, but **not in this WordPress
  install**.
* "Roll back by activating the previous theme" is meaningless — the previous theme renders
  nothing, because there are no pages. **Rollback is switching visibility back to
  `coming_soon`**, which is instant and total.
* Every step short of that switch is invisible to the public. Pages can be created, content
  imported and the theme activated with nobody able to see any of it.

### The old site's plugin is still installed and active

`BrandSure Site Infrastructure v1.7.0` describes itself as supplying *"shared styles,
behaviour, SEO tags and schema for the BrandSure service pages, the services hub /
how-it-works / pricing pages, the blog templates, the company/legal pages … and the single
global header used by every page."*

That is the entire job `brandsure-v2` now does, in code the new theme does not control. Left
active it will emit a second header, a second set of SEO tags and a second schema graph. **It
must be deactivated before the theme is activated**, not after.

---

## 1. Before you start — the launch inputs

`php tools/guard.php --launch` is the check to run rather than a memory of this list. **It now
passes.** All four are in `data/site.json`.

| # | Input | What breaks without it | Open item | Status |
|---|---|---|---|---|
| 1 | WhatsApp number | the gate, the sticky bar, every service enquiry — the primary KPI | 1 | **supplied** `+91 76249 64199` |
| 2 | Public phone number | the Call half of the sticky bar, Organization schema | 5 | **supplied** `+91 88911 99199` |
| 3 | Public email | Organization schema, the contact fallback | 5 | **supplied** `info@brandsure.in` |
| 4 | n8n base URL | every event; the funnel learns nothing | 2 | **supplied** `https://n8n.srv1944094.hstgr.cloud/webhook` |

Verified in a browser, not asserted: the gate produces a real deep link carrying the four
numbers and the session code, the Call button dials, the Organization schema carries both
contacts, and the event beacon posts to
`https://n8n.srv1944094.hstgr.cloud/webhook/landing-event`.

**One caveat that is not the website's to fix.** The site now sends events correctly; WF-03 and
the Supabase schema do not yet accept them — six separate reasons, listed in `OPEN-ITEMS.md`
item 2, from the workflow being inactive to `lead_events.source` carrying a CHECK constraint
that rejects the value `'website'`. Nothing about this blocks the cutover: every page works and
every CTA works. It means check 9 in §3 below will fail until that work is done, and that this
is expected rather than a reason to roll back.

Two more that should be settled but do not block: Growth Partnership copy sign-off (item 6) and
the share image (item 12).

---

## 2. Sequence

Numbered because the order matters. The site is in `coming_soon`, so **every step except the
last is invisible to the public** — there is no need to rush any of them, and no visitor sees a
half-built state.

**1. Confirm the launch inputs.** All four are already in `data/site.json`. Re-run
`php tools/guard.php --launch` — it must print `PASS`.

**2. Install the theme.** `wp-content/themes/brandsure-v2/` has to reach the server. On an
Atomic site there are three ways and **none of them is available through the WordPress.com MCP
tools**, which can only install themes from the WordPress.com catalogue:

* **GitHub Deployments** (Hosting → Deployments) — connect this repository, target
  `wp-content/themes/brandsure-v2`. Best option: every future change deploys by pushing.
* **SFTP/SSH** — credentials from Hosting → SFTP/SSH, upload the directory, or `wp theme
  activate` over SSH.
* **wp-admin → Appearance → Themes → Add New → Upload Theme**, from a zip of that directory.

**2a. Deactivate the conflicting plugins.** Before activating the theme, not after:

| Plugin | Why |
|---|---|
| **BrandSure Site Infrastructure 1.7.0** | supplies the old header, SEO tags and schema — a second copy of everything the theme now owns |
| **Rank Math SEO** | emits its own `<title>`, canonical, Open Graph and schema; `inc/seo.php` and `inc/schema.php` already do, and two of each is worse than either |
| **Elementor** | the brief forbids page builders in this build; nothing in the theme uses it |

**Microsoft Clarity is a decision, not a cleanup** — see §1.

**3. Create the pages.** Nine controlled slugs, four service pages, About, four legal pages, and
the blog page:

```
home  plans  digital-partner  growth-partnership  how-it-works  proof  start
built-by-brandsure  services  about  privacy-policy  terms-and-conditions
cookie-policy  disclaimer
furniture-growth-marketing-india  furniture-business-consulting-india
furniture-product-design-services-india  furniture-labour-hiring-india
```

The four service slugs and `/services/`, `/about/`, `/how-it-works/` and the legal pages **must
keep exactly these slugs** — they are the live URLs and they are deliberately not redirected
(`REDIRECTS.md` §3).

Controlled pages need no content: the template renders `data/`, and the content lock forces
`post_content` empty anyway. Create them titled and empty.

**4. Import the editable content, once.** `migration/README.md` has the command. About, the four
service pages, the four legal pages. Then **delete each seed in the commit that confirms its
import** — the guard lists any that linger, because a file that looks editable but changes
nothing is exactly the drift this project exists to prevent.

**5. Set the front page and the posts page.** Settings → Reading: front page = Home, posts page =
Blog. Permalinks: post name, with the trailing slash.

**6. Preview on a staging URL or with the theme previewer.** Walk the primary journey on a
phone: Home → Plans → Digital Partner → Start. Open the four service pages. Open a legal page
and confirm it has no CTA. Tap the WhatsApp button and read the message before sending it.

**7. Activate.** This is the switch, and it is instantaneous.

---

### Progress, as at Chat 14

| Step | State |
|---|---|
| 1. Launch inputs | **done** — all four in `data/site.json`, `--launch` passes |
| 2. Install the theme | **done by BrandSure** — `brandsure-v2` installed and activated, switched from `brandsure-theme` |
| 2a. Deactivate conflicting plugins | **outstanding** — Clarity, BrandSure Site Infrastructure 1.7.0, Rank Math and Elementor are all still active |
| 3. Create the pages | **done** — 19 pages, every slug exactly as the ownership registry expects |
| 4. Import the editable content | **done** — About, four service pages, four legal pages, verified against the seeds |
| 5. Front page / posts page | **part done** — front page is Home; **the posts page still has to be set to Blog** in Settings → Reading, which the API does not expose |
| 6. Preview | for BrandSure |
| 7. Activate | **not done** — the site remains `coming_soon` |

The old site's 17 pages are still in the trash, and WordPress renamed each of their slugs to
`…__trashed`, which is why none of them collided. Leave them there or empty the trash; do not
restore one, because a restored page reclaims its original slug and would take it from the new
page of the same name.

**8. Immediately after** — the checks in §3.

---

## 3. Immediately after activation

Run these in this order. Any failure is a reason to roll back (§5), not to fix forward on a
live site.

1. `curl -sI https://brandsure.in/pricing/` → **301** to `/plans/`, one hop.
2. `curl -sI -L http://www.brandsure.in/pricing` → reaches `https://brandsure.in/plans/` in
   **one** hop, not three. If it chains, the host-level rules in `REDIRECTS.md` §4 are firing in
   the wrong order.
3. The four service URLs and `/services/` → **200**, no redirect at all.
4. `/wp-sitemap.xml` lists the primary journey, the services, the blog and the legal pages, and
   does **not** list `/built-by-brandsure/`. No author or taxonomy sitemap.
5. `/built-by-brandsure/` → 200 with `noindex, follow`, and `robots.txt` does **not** disallow it
   (see `REDIRECTS.md` §5 for why that is deliberate).
6. `/robots.txt` allows everything and names the sitemap.
7. A URL that does not exist → the real 404, not a blank page.
8. Send one message through the gate from a real phone. Confirm it arrives, and confirm the four
   numbers and the `Ref:` code are in it.
9. Confirm `page_view` and `gate_whatsapp_click` reach n8n with `source: "website"`. **Expected
   to fail** until the six changes in `OPEN-ITEMS.md` item 2 are made — this one check is not a
   rollback trigger, because it tests the far end rather than the site.
10. Google Search Console: submit the sitemap, request re-crawl of `/plans/`.

---

## 4. The crawl gap

Step 2 of `REDIRECTS.md` §6 is the one that cannot be done from inside this repository, and it
is the one most likely to be skipped:

> Crawl the live site, export every indexed URL, and confirm each one either exists in the new
> build at the same path or has a rule in the redirect table.

The redirect table is short **because** the URL inventory was reasoned from the supplied files
rather than from a crawl. A page that was published and is not in those files would land on the
new 404 with no rule. Run the crawl before activating; anything it turns up is a redirect to add,
not a surprise to discover in Search Console six weeks later.

---

## 5. Rolling back

**Set visibility back to `coming_soon`.** One call, instant, total: the public sees the
coming-soon screen again and nothing else. That is a far better rollback than the theme switch
this document originally described, and it exists because the site starts from `coming_soon`
rather than from a live build.

Nothing in the cutover is destructive. No old file is deleted; the old plugins are deactivated
rather than removed, so re-activating them restores the previous arrangement exactly. The only
irreversible action in the sequence is the `/pricing/` 301 — a permanent redirect is cached hard
by browsers — which is why it is the one content redirect in the build and why it points at a
page that exists before the site is made public.

Roll back for: the gate not producing a WhatsApp message, a redirect chain, or any page that
fails to render. **Not** for events failing to reach n8n (§1) and not for cosmetic differences —
note those and fix them in the theme.

---

## 6. The first week

| When | Check |
|---|---|
| Day 1 | `gate_whatsapp_click` count is non-zero. If it is zero and traffic is not, the gate is broken, not unpopular. |
| Day 1 | Search Console → Coverage: no spike in 404s. A spike means the crawl in §4 was skipped. |
| Day 2 | Search Console → Enhancements: FAQ and Breadcrumb items detected on the pages that carry them, and **no** FAQ item on a page with no visible questions. |
| Day 3 | Rendered `/plans/` on a real phone, not an emulator. The compare table is the most photographed thing on the site. |
| Week 1 | `plan_selected` split across the three routes. If Growth Partnership is zero, the qualification CTA is not being found. |
| Week 1 | Confirm no `plan=Growth Partnership` appears in any n8n payload. It cannot come from this site, so if it appears the source is an old link somewhere else. |

---

## 7. What is deliberately not in this plan

* **No content freeze on the old site.** It is not being edited, so there is nothing to freeze.
* **No database migration.** Blog posts keep their slugs and are untouched; nothing else moves.
* **No analytics migration.** The theme carries no analytics (`OPEN-ITEMS.md` item 11). If GA or
  a Meta pixel is added at launch, the Cookie and Privacy policies must be updated *before* the
  tags go live, and the site will then need a consent mechanism it does not currently require.
* **No redirect for any URL that still exists.** The temptation at cutover is to tidy slugs.
  Every one of the four service URLs is a ranking asset; renaming any of them costs more than
  any tidiness is worth.
