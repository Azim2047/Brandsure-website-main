# BrandSure Website v2 — Build Manifest

*Every page that will exist in the new build. Nothing outside this list gets built without an
update to this file and to `SOURCE-OF-TRUTH.md`.*

Legend:

* **Ownership** — `CONTROLLED` = developer-owned, JSON + template, WordPress block editor
  disabled. `EDITABLE` = WordPress-owned, block editor, no JSON copy exists. (Override 4.)
* **Disclosure** — `PUBLIC-FULL` may show the public prices; `PUBLIC-MASKED` shows the plan but
  no commercial mechanism; `PUBLIC-NEUTRAL` carries no commercial facts at all.

---

## A. Primary journey (header nav)

### 1. `/` — Home
| | |
|---|---|
| **Purpose** | In five seconds on a phone: this is a furniture-only company that has run furniture factories, and it can build the other half of my business. Then hand to Video 1 and the gate. |
| **Content source** | `data/pages/home.json` + `data/site.json` + `data/proof.json` + `data/videos.json`. Positioning derives from Architecture v3 §2/§3; proof numbers from Numbers Sheet A4. Old homepage positioning is **not** carried over. |
| **Template** | `templates/page-home.php` |
| **Ownership** | CONTROLLED |
| **Primary CTA** | `See if we can help →` `/start/` (sticky WhatsApp + Call bar throughout) |
| **Disclosure** | PUBLIC-NEUTRAL. **No plan pricing** — no fee, no setup, no plan name. The factory credentials and the one client result (with its mandatory footnote) do appear: both are PUBLIC per SOURCE-OF-TRUTH §5.1, and Home has to carry its own proof while Video 1 does not exist. Video 1 is public and carries no prices. |
| **Build chat** | 2 — **built** |

### 2. `/plans/` — The three plans
| | |
|---|---|
| **Purpose** | Show three ways to build the growth side, position Digital Partner as where most start, and route each visitor to the right prefill. Does not explain the plans in depth — that is the funnel's job. |
| **Content source** | `data/plans.json` + `data/commercial.json` + `data/pages/plans.json`. Prices from Numbers Sheet A1/A2 only. GP column masked per Override 1. |
| **Template** | `templates/page-plans.php` |
| **Ownership** | CONTROLLED |
| **Primary CTA** | `Help me choose →` `/start/`. Per-plan: `Ask about Agency` (`plan=Agency`), `Ask about Digital Partner` (`plan=Digital Partner`), and for GP **`Check If My Business Qualifies`** (`interest=Growth Partnership`). |
| **Disclosure** | PUBLIC-FULL for Agency and Digital Partner. PUBLIC-MASKED for Growth Partnership: the compare-table fee cell reads *"Commercials explained after a business review."* No Video 2 embed. |
| **Build chat** | 4 — **built**; compare table corrected in Chat 12 |

**Corrected in Chat 12.** The compare row *"Every enquiry written down"* had
`fact:digital_partner_capacity` in its Digital Partner cell, so the table answered that question
with *"1 city · up to 300 new enquiries / month"* — a different question's answer, in the most
photographed artefact on the site. The cell now reads *"Yes"*. The capacity fact is **not** given
a row of its own: it is stated in full on `/digital-partner/`, and a second copy in the table is
a second place it can go stale.

The audit also showed the table stated the Agency fee but never what Agency costs to run, while
`agency_adspend_range` sat defined in `commercial.json` and used nowhere. An *"Ad budget you
pay"* row now carries it, alongside Digital Partner's starting budget and Growth Partnership's
approved sentence — so the one number an Agency buyer needs after the fee is on the page that
promises full transparency for that plan.

### 3. `/digital-partner/` — Digital Partner detail
| | |
|---|---|
| **Purpose** | Make the velocity product concrete: what BrandSure does, what the owner does, what it costs in month 1 and after, and what capacity it carries. Transparency converts here. |
| **Content source** | `data/pages/digital-partner.json` + `data/commercial.json`. Numbers Sheet A2. Video 3 from `data/videos.json` (public). |
| **Template** | `templates/page-digital-partner.php` |
| **Ownership** | CONTROLLED |
| **Primary CTA** | `Start with Digital Partner →` → `/start/` with `plan=Digital Partner` |
| **Disclosure** | PUBLIC-FULL — ₹75,000 + ₹25,000, ₹1,00,000 month one, 300 enquiries, 45 days, 3-month minimum, 5-order break-even. Video 3 embedded publicly. **Deliberately omitted:** the 300 → 90 → 35 → 9 breakdown from Video 3 S10. Architecture v3 §5 lists what this page may show and that is not on it, and an unfootnoted conversion claim needs the qualification the video's narration carries. The six leak points from Video 3 S2 belong to `/how-it-works/` and are not repeated here. |
| **Build chat** | 5 — **built** |

### 4. `/growth-partnership/` — Desire and structure only
| | |
|---|---|
| **Purpose** | Make Growth Partnership highly visible and highly desirable, and make the reader want a business review — while disclosing no commercial mechanism whatsoever. |
| **Content source** | `data/pages/growth-partnership.json`. Copy is constrained to the three approved sentences in Override 1 plus non-commercial structure: nested-boxes diagram, what we invest in (icons, no rupee values), the five stage names, the seven "always yours" items, the honest-qualification paragraph. |
| **Template** | `templates/page-growth-partnership.php` |
| **Ownership** | CONTROLLED |
| **Primary CTA** | **`Check If My Business Qualifies →`** → `/start/` with **`interest=Growth Partnership`** (never `plan=`) |
| **Disclosure** | PUBLIC-MASKED, strictest. No `₹` figure of any kind. No duration. No deposit, no setup fee, no percentage, no weekly rhythm. Stages named only: Foundation → Stability → Profit per Order → Scale → Stable at Scale. No Video 4 or 5, and neither is named. GP-page blocklist in the guard applies. |
| **Build chat** | 6 — **built, copy pending approval** (see `OPEN-ITEMS.md` item 6) |

### 5. `/how-it-works/` — The chain
| | |
|---|---|
| **Purpose** | Diagnosis before offer. Show the one chain, where the money leaks, who does what, and answer the common questions. The landing page for problem-intent search. |
| **Content source** | `data/pages/how-it-works.json` + `data/faq.json` (keyed to this page). Chain from Language Sheet B1; leak points from Video 3 S2; questions from Language Sheet B5. **"Who does what" is answered in the FAQ, not as a lanes diagram** — the honest answer is "it depends on the plan", the per-plan version already exists on `/plans/` and `/digital-partner/`, and a generic third copy would be the first one to go out of date. |
| **Template** | `templates/page-how-it-works.php` |
| **Ownership** | CONTROLLED |
| **Primary CTA** | `Send us four numbers →` `/start/` |
| **Disclosure** | PUBLIC-NEUTRAL. `FAQPage` schema emitted in Chat 11 (this page has a visible FAQ block; guard R6 enforces the pairing both ways). |
| **Build chat** | 7 — **built** |

### 6. `/proof/` — Credentials and the case
| | |
|---|---|
| **Purpose** | Credibility-first landing for warm contacts and email-signature traffic. Factory credentials, the one client result with its footnote, and the client websites BrandSure has built. |
| **Content source** | `data/proof.json`. Numbers Sheet A4 exactly, footnote mandatory. Client site list is a data array. |
| **Template** | `templates/page-proof.php` |
| **Ownership** | CONTROLLED |
| **Primary CTA** | `Start →` `/start/` |
| **Disclosure** | PUBLIC-FULL for credentials (50,000 sq.ft · 3 factories · ₹87 Cr+) and the ₹45L → ₹87L+ case **with** the footnote. No plan pricing. **The client-sites section is absent** while `data/proof.json` has no clients (OPEN-ITEMS item 8); a short block explains why there is one result rather than a wall of logos. |
| **Build chat** | 7 — **built** |

### 7. `/about/` — Founder, team, Instagram
| | |
|---|---|
| **Purpose** | The human layer. Who runs BrandSure, why furniture, the team, the Instagram feed. Trust, not selling. |
| **Content source** | **WordPress block editor.** Founder copy worth preserving may be lifted from the old `about.html` and pasted in as the initial editor content — once, at migration. After that WordPress is the only copy. No JSON. |
| **Template** | `page.php` (shared shell) with an About body-class; team and Instagram render from editor blocks and a shared component. |
| **Ownership** | **EDITABLE** |
| **Primary CTA** | Shared page-foot CTA component → `/start/` |
| **Disclosure** | PUBLIC-NEUTRAL. No commercial facts. The blocklist applies to editor content through `inc/content-guard.php` — held as a draft at save, elided at render. |
| **Team and Instagram** | Core blocks, not a plugin or a settings screen. The team photo is an Image block; the Instagram feed is a Gallery block whose images each link to their post via *Link to → Custom URL*. The old site used admin-managed cards behind a shortcode, which is a second editing surface for no gain — and with no Graph API token supplied, admin-managed cards were the only real option there anyway. Client supplies the photos and the profile link through the editor. |
| **Build chat** | 8 — **built** (content seeded from `migration/about.html`, imported once) |

### 8. `/start/` — The gate
| | |
|---|---|
| **Purpose** | The only conversion mechanic on the public site. Four numbers → a prefilled WhatsApp deep link carrying a session code. Everything else exists to get a visitor here. |
| **Content source** | `data/pages/start.json` + `data/site.json` (WhatsApp number, prefill templates). Four fields per Video 1 S9: monthly sales · current ad spend · showrooms · monthly production capacity. |
| **Template** | `templates/page-start.php` |
| **Ownership** | CONTROLLED |
| **Primary CTA** | **`Send on WhatsApp`** — the site's PRIMARY KPI (`gate_whatsapp_click`). A link, not a form submission: its href is a valid WhatsApp deep link on render and JavaScript only rewrites it as the owner types, so it still works with JavaScript off. Carries `plan=` or `interest=` when the visitor arrived from a plan CTA. |
| **Disclosure** | PUBLIC-NEUTRAL. The gate states no prices. Nothing is POSTed — the four numbers reach BrandSure only inside the message the owner sends from their own device, and the event stream records that the gate was used, never what was in it. |
| **Build chat** | 3 — **built** |

---

## B. Special entry

### 9. `/built-by-brandsure/?ref=clientslug`
| | |
|---|---|
| **Purpose** | Catch the warmest traffic in the system — a visitor who just used a client's BrandSure-built website. *"You just visited [Client]'s website. BrandSure built it."* |
| **Content source** | `data/pages/built-by.json` + a `clients` array in `data/proof.json` keyed by slug. Unknown or missing `ref` falls back to a generic version. |
| **Template** | `templates/page-built-by.php` |
| **Ownership** | CONTROLLED |
| **Primary CTA** | `Start →` `/start/` |
| **Disclosure** | PUBLIC-NEUTRAL. `noindex` (a referral surface, not a search surface — and it stops one near-identical page being indexed per `?ref=`). Fires `builtby_view` with the ref. **Generic for every visitor** while no client is cleared for naming (OPEN-ITEMS item 8); the `?ref=` lookup is built and tested, so adding a client is a data edit to `proof.json` alone. |
| **Build chat** | 7 — **built** |

---

## C. Other Services (footer only)

### 10. `/services/` — Other Services index
| | |
|---|---|
| **Purpose** | One place that names the four standalone offers and sends the visitor to the right one. On-page title is **"Other Services"**. |
| **Content source** | `data/pages/services-index.json` — four cards, one line each, plus the closing link to `/plans/`. The index is a routing surface, so it stays controlled. |
| **Template** | `templates/page-services-index.php` |
| **Ownership** | CONTROLLED |
| **Primary CTA** | The four service cards; secondary small link to `/plans/` |
| **Disclosure** | PUBLIC-NEUTRAL |
| **Build chat** | 9 — **built** |

### 11–14. The four standalone service pages

All four share one template and one three-part shape (Architecture v3 §6): satisfy the search
intent first (~70% of the page) → own enquiry path → one quiet bridge at the bottom.

| | |
|---|---|
| **Template** | `templates/page-service.php` — shared shell, the enquiry block and the bridge; **body copy from the WordPress editor**. The four slugs are bound to this template in code, so neither the enquiry routing nor the bridge can be lost by an edit. |
| **Ownership** | **EDITABLE** — all four |
| **Content source** | WordPress block editor, seeded once from `migration/<slug>.html`. About 1,250–1,700 words each, carried over from the old pages — the copy is strong and already in persona language. **Stripped on the way in:** the "Two ways to buy this service" section (plan pricing belongs to `/plans/`, and the old copy carried a stale ₹80,000 that is in no Numbers Sheet), the hand-written Growth Partnership bridges, "Related help" (the footer owns Other Services), and the inline enquiry asks whose buttons no longer exist. **Kept:** the "Written and reviewed by" byline, which is real E-E-A-T. **Reworded:** `retargeting`, `SKU`, and two untranslated uses of `CRM`. |
| **Disclosure** | PUBLIC-NEUTRAL. No plan pricing on any service page. |
| **Build chat** | 9 — **built** |

| # | URL | Purpose | Enquiry prefill | Bridge |
|---|---|---|---|---|
| 11 | `/furniture-growth-marketing-india/` | Capture "furniture marketing" search intent; describe the standalone marketing engagement | `service=marketing` | Yes — one quiet Growth Partnership bridge at the bottom |
| 12 | `/furniture-business-consulting-india/` | Standalone consulting engagement; stays a standalone offer | `service=consulting` | Yes |
| 13 | `/furniture-product-design-services-india/` | Standalone product design / BOM / catalogue work | `service=design` | Yes |
| 14 | `/furniture-labour-hiring-india/` | Pure SEO capture for carpenter/upholstery hiring. **A genuinely different buyer.** | `service=labour` | **No bridge.** Its only link out is the footer. |

The bridge, where used, is exactly one block, once, at the bottom:

> **Need this as part of a bigger transformation?**
> Product, pricing and catalogue development are also added in stages under BrandSure's Growth Partnership.
> **Explore Growth Partnership →**

`Service` schema on all four. `FAQPage` schema **only** where the page renders its own visible
FAQ block — never copied from `/how-it-works/`. It is derived from what is actually in
`post_content`: the `<details>` blocks are read back out, so deleting a question deletes it from
the search result too.

**Corrected in Chat 11.** Insisting the schema be derived rather than assumed found a real
defect in the Chat 9 migration: the old pages held their common questions in
`<details><summary>`, and converting them to blocks dropped every `<summary>`. All four pages
had been left with a heading followed by ten to twelve unattributed answer paragraphs — read
literally, prose that answers questions nobody asked. The 49 questions were recovered from the
old pages, matched to their answers in order (each verified against the old text before
attaching), and restored as core Details blocks styled to match the theme's own accordion. The
same conversion had also flattened each page's `<h1>` into a duplicate first paragraph, so every
service page opened by repeating its own title; those four paragraphs are gone.

A `Question` node needs a question, so only a `<details>` whose summary ends in `?` becomes one.
That keeps the consulting page's opening symptom list — *"Sales are low"*, *"Everything needs
the founder"* — out of the schema without asking an editor to know the difference.

**No plan pricing, enforced.** `inc/content-guard.php` carries a page-scoped rule for these four
slugs: a plan fee will not publish here. The fee is not a private fact — `/plans/` states it in
full — but a fee on a service page is a second place it lives, editable by staff, and it goes
stale. "Customers keep asking the price" is a reasonable thing for an editor to think, which is
why the answer is a rule rather than a convention.

---

## D. Content

### 15. `/blog/` — index
| | |
|---|---|
| **Purpose** | SEO surface and a place to publish. |
| **Content source** | WordPress posts |
| **Template** | `templates/archive-blog.php` |
| **Ownership** | EDITABLE |
| **Primary CTA** | Card link to each post; page-foot CTA → `/start/` |
| **Disclosure** | PUBLIC-NEUTRAL |
| **Build chat** | 10 — **built** |

### 16. `/blog/<post>/` — single post
| | |
|---|---|
| **Purpose** | Rank, then hand the reader to Video 1 and the gate. |
| **Content source** | WordPress post content (editor) |
| **Template** | `templates/single-post.php` — shared shell + a Video-1 gate card appended after the post body by the theme, not by the author |
| **Ownership** | EDITABLE (body) / CONTROLLED (the appended gate card) |
| **Primary CTA** | Gate card → `/start/` |
| **Disclosure** | PUBLIC-NEUTRAL. `Article` schema. Guard blocklist applies to post content at render. |
| **Build chat** | 10 — **built** |

---

## E. Legal

### 17–20. `/privacy-policy/` · `/terms-and-conditions/` · `/cookie-policy/` · `/disclaimer/`
| | |
|---|---|
| **Purpose** | Compliance. Must be editable by backend staff without a developer. |
| **Content source** | WordPress block editor. Existing legal text migrated in once. |
| **Template** | `page.php` (shared shell, narrow prose column) |
| **Ownership** | EDITABLE |
| **Primary CTA** | None. These pages deliberately carry no conversion CTA. |
| **Disclosure** | PUBLIC-NEUTRAL. No commercial terms of any plan. **Corrected on migration:** the old Cookie and Privacy policies declared Google Analytics, Google Ads remarketing and the Meta pixel, none of which this theme uses — its whole third-party surface is a `wa.me` link and YouTube on tap. Those declarations were removed, the analytics section now describes the site's own event recording, and `bs_vid` is named and explained as `EVENTS-AND-INTEGRATION.md` §4 promised. The rest of the reviewed legal text is carried over unchanged. See OPEN-ITEMS item 11. |
| **Build chat** | 10 — **built** |

---

## F. System pages (no URL of their own)

| Item | Template | Ownership | Notes |
|---|---|---|---|
| 404 | `404.php` + `data/pages/404.json` | CONTROLLED | Routes to Home and `/start/`, plus cards to Plans, How it works, Proof and Other Services. Never a dead end. **Built in Chat 12** — it was listed here from Chat 0 and no chat had built it, so WordPress was serving `index.php`'s empty loop: a blank page under a header. Found by the audit, which is what an audit is for. |
| Search results | not built | — | No public search in this build. |
| `/pricing/` | — | — | 301 → `/plans/`. See `REDIRECTS.md`. |

---

## G. Build order

Chat 0 (this one) established the project. The remaining order follows Architecture v3 §10,
adjusted so the conversion mechanic is built before the pages that feed it.

| Chat | Build |
|---|---|
| 1 | `inc/data.php`, `inc/content-ownership.php`, `inc/guard.php`, `inc/events.php`; the shared shell components (header, footer, sticky bar, section, CTA, button); `brandsure.css` on top of `tokens.css`; the SVG sprite. `schema.php`, `seo.php` and `redirects.php` are Chat 11 — they need real pages to attach to. |
| 2 | `/` Home · **done** |
| 3 | `/start/` — the gate, the WhatsApp prefill, the `bs_vid` session code · **done** |
| 4 | `/plans/` · **done** |
| 5 | `/digital-partner/` · **done** |
| 6 | `/growth-partnership/` · **done** |
| 7 | `/how-it-works/` · `/proof/` · `/built-by-brandsure/` · **done** |
| 8 | `/about/` — editable-page pattern proved here first · **done** |
| 9 | `/services/` index + the four editable service pages, with the SEO migration · **done** |
| 10 | `/blog/` + posts + the four legal pages · **done** |
| 11 | Events wired to n8n · schema · `/pricing/` 301 · sitemap · **done** |
| 12 | Guard run, mobile QA at 360/390/430 px, numbers and language audit, replacement plan for the live site · **done** |
| 13 | SEO audit of the rendered output · **done** |

---

## H. The Chat 12 audit

Run against the **rendered** documents — head, meta tags and JSON-LD included — rather than
against source files, which is a different question from the one `tools/guard.php` answers. 21
surfaces (the 20 in this manifest plus the 404), at 360, 390 and 430 px.

| Audit | Result |
|---|---|
| Disclosure blocklist, whole document | clean, 21/21 |
| Growth Partnership strict list, visible copy | clean — no money, no duration, no deposit, no fee, no percentage |
| Language Sheet B3, visible copy | clean, 21/21 — broader than the build guard, which reads `data/*.json` and the seeds only |
| Route contract (Override 3) | clean — every Growth Partnership CTA passes `interest=`, none passes `plan=` |
| Layout, 63 renders | no horizontal overflow, no console errors, one `h1` per page, no heading-level skips, every tap target ≥ 44 px, no text under 12 px, every input ≥ 16 px, every image has `alt`, every input has a label |
| WCAG AA contrast | every text element passes its floor |
| Progressive enhancement | with JavaScript off: nav in flow and usable, hero visible, no overflow |

### Every figure on the site, and where it comes from

Each is checked against `data/commercial.json`, whose values trace to the Master Numbers Sheet.

| Figure | Source |
|---|---|
| 50,000 sq.ft · 3 factories · ₹87 Cr+ | `credential_*` (A4) |
| ₹45 lakh → ₹87 lakh+ in about 5 months | `client_result` (A4), always with its footnote |
| ₹45,000 + GST · ₹25,000 setup · ₹50,000 – ₹2,00,000 ad budget | `agency_*` (A1) |
| ₹75,000 + GST · ₹1,00,000 month one · ₹1,00,000 ad budget · 300 enquiries · 1 city · 45 days · 5 orders · ₹15,000 profit per order | `digital_partner_*` (A2) |
| 3 months minimum term | `minimum_term` (A1, A2) |

Two figures render that are **not** commercial facts, both deliberate and both recorded here so
they are a decision rather than an oversight:

* **180 days**, in the Cookie Policy. The `bs_vid` cookie's lifetime, from
  `site.json` → `integration.session_cookie_days`. It is a technical fact about the site, and the
  policy is required to state it.
* **"a ₹80,000 sofa"**, on `/furniture-growth-marketing-india/`. A customer's ticket size, not a
  BrandSure price — reviewed copy carried over from the old page, and the concrete detail that
  makes an owner feel the page was written about his business. Left as written. The page-scoped
  pricing rule in `inc/content-guard.php` deliberately does not match it: a rule that flagged
  this would be training people to ignore the rule.

Nothing else on the public site states a number.


---

## I. The Chat 13 SEO audit

Chat 11 built the SEO layer and verified each piece as it was written. This audits the finished
output as a whole, which is a different question: uniqueness, resolution and reachability are
properties of the site, not of a file, so they can only be checked once every page exists.

| Check | Result |
|---|---|
| Titles | 21 unique, none over 60 characters, none thin |
| Meta descriptions | 21 unique, all within 160 characters, none thin |
| Canonicals | absolute, self-referencing, no query strings; none on the 404, correctly |
| Robots | `noindex, follow` on `/built-by-brandsure/` and the 404 only |
| H1 | exactly one per page, none a lazy duplicate of its title |
| Open Graph | complete on all 20 indexable pages; none on the 404, deliberately |
| Schema graphs | every required property present, every `@id` reference resolves, breadcrumb positions 1..n ending on the page itself, every `FAQPage` question verified to appear in the page's own HTML |
| Internal links | every one resolves to a page in the build |
| Orphans | none, other than the two that are orphaned on purpose |

### What it found

**1. `/digital-partner/` had no inbound link from anywhere on the site.** The plan card's only
control went straight to the gate, and the page is not in the header — so the detail page for
the plan `site.json`'s own nav note calls *"reached from /plans/"* was reachable only by typing
the URL. `/growth-partnership/` was little better: three inbound links, all from service-page
bridges, none from `/plans/`.

Both plan cards now carry a quiet *"See what's included →"* link beneath the button. It is
derived from the ownership map rather than from a key in `plans.json`, so it cannot point at a
page that does not exist — Agency has a slug but no page and correctly gets no link, and the day
an `/agency/` page is built the link appears on its own.

**2. The blog index description was 172 characters.** Guard rule R10 checks `data/pages/*.json`,
and the blog index is the one public surface whose strings live in `site.json` instead — so the
rule covered every page but one, in exactly the place nobody looks. The copy is tightened to 142
and **R10 now reads the `seo.blog` block too**, negative-tested.

**3. A long post title overflowed `<title>`.** The theme appended `" — BrandSure"` with no length
check, so a 56-character headline produced a 68-character title that Google truncates
mid-sentence. The suffix is now dropped when it would push the title past 60 — Google appends the
site name itself when it wants to, so nothing is lost.

**4. The 404 emitted partial Open Graph** — a title and no `og:url`, because it has no canonical
URL to give. It now emits none at all. Nobody should be sharing a "page not found", and a card
with a title and no address is worse than no card.

### Two orphans that stay orphans

`/built-by-brandsure/` is a referral surface reached from client websites and is `noindex` — an
internal link to it would defeat the point. The 404 is reached by accident, never by a link.

### Three findings that were the auditor's fault, not the site's

Recorded because a corrected check is worth as much as a corrected page: the fixture harness
generates a `/blog/page/2/` link WordPress would serve for real; an `h1` matching its `<title>`
is correct when the brand suffix was deliberately dropped; and the 404's missing Open Graph is
the intended behaviour. The auditor was corrected in each case, with the reason written at the
check.

---

## §J. Chat 15 — Layer 4 v4, mobile-first, motion and the WhatsApp panel

The 5 September 2026 Layer 4 pack arrived with a brief: 99% of visitors are on phones, the pages
feel static, and there is no WhatsApp popup. Four pieces of work, in the order they had to happen
— the facts first, because everything else renders them.

### 1. Commercial corrections

Full table in `LAYER-4-V4-CHANGE-REGISTER.md`. The two that mattered most were both advertising
budgets the build should never have carried: **₹1,00,000/month** for Digital Partner — the exact
figure numbers.v2.js v2 removed by name as *"forbidden"* — and a **₹50,000–₹2,00,000** Agency
range labelled "suggested". Neither survives, and guard R14 keeps them out. The public
**five-stage Growth Partnership roadmap** went the same way: withdrawn by v4 §4, and by then also
wrong, since numbers.v2.js had replaced those five stages with four private, dated ones.

Contacts moved to `hello@brandsure.in` and WhatsApp `918891199199` on numbers.v2.js's authority.
That is the one change in this chat that overrides something BrandSure supplied directly, and it
is flagged for confirmation as `OPEN-ITEMS.md` item 13 rather than buried in a diff.

### 2. The WhatsApp panel

A bottom sheet on phones launched from the sticky bar, a corner panel on desktop, one piece of
markup, every option a real `wa.me` link so it works with no JavaScript. Contextual on service
pages; labour hiring offers only labour hiring. Absent from `/start/`, where the four-number
handoff is the action and already carries the visitor's own figures.

### 3. Mobile-first

Touch targets to 48px everywhere — the worst offenders were **13 footer links at 16px tall on all
21 pages**. Notes lifted from 14px to body size, because the sentence saying what a number does
*not* mean is not small print. And the plan comparison stopped being a desktop table in a
horizontal scroller: below 44rem each row is a card with all three plans visible, which on a site
where 99% of readers are on phones means two of the three plans stopped being hidden behind a
swipe.

### 4. Motion

Hero entrance, one-shot section reveals, the eight-step chain lighting in sequence, press states,
FAQ fades, a sticky-header state. All of it under a class the script adds, so nothing can hide
content; reduced motion turns it off rather than down; no figure is ever animated through a value
that is not real.

### Bugs found by driving a browser, not by reading code

**An invisible full-screen scrim was intercepting every tap on the site** — `display: block` in a
class chain beating `[hidden]` from the UA stylesheet. Nothing was clickable. **Eleven of twelve
home-page sections could be left permanently blank** by jumping to the bottom of the page, because
an IntersectionObserver never fires for elements that never intersect; the reveal is a scroll
sweep now. And the scroll restore after closing the sheet *animated*, because the page sets
`scroll-behavior: smooth`.

A fourth was self-inflicted and caught by the guard: rewriting the ad-budget row produced a
**duplicate** of a "Who pays for the ads" row that already existed. The row was deleted rather
than rewritten.

### Evidence

21 surfaces, complete documents, over HTTP: no touch target under 48px at 360/390/430; no
horizontal overflow or clipped text at eight widths; no contrast failure; every page fully
readable with JavaScript off; disclosure, language and route-contract audits clean; guard PASS.
Not tested on a real handset or in WhatsApp's in-app browser — see `MOBILE-AND-INTERACTION.md` §7.
